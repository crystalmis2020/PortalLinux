import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

// Execute the widget's actual call functions with controlled browser/network timing.
const view = readFileSync(process.env.MESSENGER_VIEW_PATH
    || new URL('../../resources/views/layout/portal-messenger.blade.php', import.meta.url), 'utf8');
const stateSource = view.slice(view.indexOf('        const state = {'), view.indexOf('        const elements = {'));
const functions = [...view.matchAll(/^        (?:async )?function \w+\([^\n]*\) \{[\s\S]*?^        \}/gm)]
    .map(([source]) => source).join('\n');

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
    return { promise, resolve, reject };
}

async function settle() {
    for (let index = 0; index < 20; index++) await Promise.resolve();
}

function harness() {
    const signals = [];
    const connections = [];
    const timers = new Map();
    const track = { stopped: false, stop() { this.stopped = true; } };
    const stream = { getTracks: () => [track] };
    let nextTimer = 1;
    const elements = { localAudio: {}, remoteAudio: {} };
    const description = (type) => ({ type, sdp: 'v=0\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111\r\n' });

    class PeerConnection {
        constructor() {
            this.connectionState = 'new';
            this.remoteDescription = null;
            this.candidates = [];
            connections.push(this);
        }
        addTrack() {}
        async createOffer() { return description('offer'); }
        async createAnswer() { return description('answer'); }
        async setLocalDescription(value) {
            this.localDescription = value;
            this.onicecandidate?.({ candidate: { candidate: 'candidate:local', sdpMid: '0', sdpMLineIndex: 0 } });
        }
        async setRemoteDescription(value) { this.remoteDescription = value; }
        async addIceCandidate(candidate) { this.candidates.push(candidate); }
        close() { this.changeState('closed'); }
        changeState(value) {
            this.connectionState = value;
            this.onconnectionstatechange?.();
        }
    }

    const context = vm.createContext({
        root: { dataset: { initialContacts: JSON.stringify([{ id: 2, full_name: 'Contact', is_online: true }]) } },
        elements,
        console: { error() {} },
        window: {
            isSecureContext: true,
            RTCPeerConnection: PeerConnection,
            crypto: { randomUUID: () => `call-${nextTimer++}` },
            portalMessenger: { openForUser() {} },
            setTimeout(callback, delay) {
                const id = nextTimer++;
                timers.set(id, { callback, delay });
                return id;
            },
            clearTimeout(id) { timers.delete(id); },
        },
        navigator: { mediaDevices: { getUserMedia: async () => stream } },
        RTCPeerConnection: PeerConnection,
        RTCIceCandidate: class { constructor(value) { Object.assign(this, value); } },
        MediaStream: class { tracks = []; addTrack(value) { this.tracks.push(value); } },
        btoa: (value) => Buffer.from(value, 'binary').toString('base64'),
        atob: (value) => Buffer.from(value, 'base64').toString('binary'),
        sendSignal: async (userId, payload) => { signals.push({ userId, ...payload }); },
    });
    vm.runInContext(`${stateSource}\n${functions}
        sendCallSignal = (userId, payload) => sendSignal(userId, payload);
        setCallPresentation = (contact, status, mode) => { state.call.mode = mode; };
        updateCallButtonState = updateCallModalUI = stopCallRingtone = () => {};
        togglePanel = setComposeMode = () => {};
        openConversation = async () => {};
        globalThis.state = state;
        state.selectedUserId = 2;
    `, context);

    return { context, state: context.state, signals, connections, timers, stream, track, elements };
}

function incoming(h) {
    Object.assign(h.state.call, {
        currentCallId: 'incoming-1', partnerId: 2, mode: 'incoming',
        offer: { type: 'offer', sdp: 'v=0\r\n' },
    });
}

test('outgoing ICE waits for the offer to be delivered', async () => {
    const h = harness();
    const delivery = deferred();
    h.context.sendSignal = async (userId, payload) => {
        h.signals.push({ userId, ...payload });
        if (payload.signal_type === 'offer') await delivery.promise;
    };
    const start = h.context.startOutgoingCall();
    await settle();
    assert.deepEqual(h.signals.map((signal) => signal.signal_type), ['offer']);
    delivery.resolve();
    await start;
    assert.deepEqual(h.signals.map((signal) => signal.signal_type), ['offer', 'ice-candidate']);
});

test('answer applies queued remote ICE and sends local ICE after the answer', async () => {
    const h = harness();
    incoming(h);
    h.state.call.pendingCandidates.push({ candidate: 'candidate:remote' });
    await h.context.answerIncomingCall();
    assert.equal(h.connections[0].remoteDescription.type, 'offer');
    assert.equal(h.connections[0].candidates[0].candidate, 'candidate:remote');
    assert.deepEqual(h.signals.map((signal) => signal.signal_type), ['answer', 'ice-candidate']);
    h.connections[0].changeState('connected');
    assert.equal(h.state.call.mode, 'connected');
});

test('a temporary disconnection recovers without hanging up', async () => {
    const h = harness();
    incoming(h);
    await h.context.answerIncomingCall();
    const connection = h.connections[0];
    connection.changeState('connected');
    connection.changeState('disconnected');
    assert.equal(h.state.call.peerConnection, connection);
    assert.equal(h.track.stopped, false);
    assert.equal(h.timers.size, 1);
    connection.changeState('connected');
    assert.equal(h.timers.size, 0);
    assert.equal(h.state.call.mode, 'connected');
    assert.equal(h.signals.some((signal) => signal.signal_type === 'hangup'), false);
});

test('a persistent disconnection ends the call after the recovery window', async () => {
    const h = harness();
    incoming(h);
    await h.context.answerIncomingCall();
    h.connections[0].changeState('disconnected');
    const timer = [...h.timers.values()].find(({ delay }) => delay === 10000);
    assert.ok(timer);
    await timer.callback();
    assert.equal(h.state.call.mode, 'idle');
    assert.equal(h.track.stopped, true);
    assert.equal(h.signals.at(-1).signal_type, 'hangup');
});

for (const direction of ['outgoing', 'incoming']) {
    test(`${direction} hangup while microphone permission is pending stops the late stream`, async () => {
        const h = harness();
        const microphone = deferred();
        h.context.navigator.mediaDevices.getUserMedia = () => microphone.promise;
        if (direction === 'incoming') incoming(h);
        const start = direction === 'incoming' ? h.context.answerIncomingCall() : h.context.startOutgoingCall();
        await h.context.finishCallSession();
        microphone.resolve(h.stream);
        await start;
        assert.equal(h.track.stopped, true);
        assert.equal(h.connections.length, 0);
        assert.equal(h.signals.length, 0);
        assert.equal(h.state.call.mode, 'idle');
        assert.equal(h.elements.localAudio.srcObject, null);
    });
}

test('late microphone rejection from an old call does not end a new call', async () => {
    const h = harness();
    const microphone = deferred();
    h.context.navigator.mediaDevices.getUserMedia = () => microphone.promise;
    const start = h.context.startOutgoingCall();
    await h.context.finishCallSession();
    incoming(h);
    microphone.reject(new Error('Permission denied'));
    await start;
    assert.equal(h.state.call.currentCallId, 'incoming-1');
    assert.equal(h.state.call.mode, 'incoming');
});

test('an answer arriving before the offer HTTP response does not revert to ringing', async () => {
    const h = harness();
    h.context.sendSignal = async (userId, signal) => {
        h.signals.push({ userId, ...signal });
        if (signal.signal_type === 'offer') {
            await h.context.handleCallSignal({ sender: { id: 2 }, signal: {
                call_id: signal.call_id, signal_type: 'answer', sdp: { type: 'answer', sdp: 'v=0\r\n' },
            } });
        }
    };
    await h.context.startOutgoingCall();
    assert.equal(h.state.call.mode, 'connecting');
    assert.equal(h.state.call.outgoingTimeoutId, null);
});

test('late connection events from an ended call cannot terminate a new call', async () => {
    const h = harness();
    await h.context.startOutgoingCall();
    const oldConnection = h.connections[0];
    await h.context.finishCallSession();
    incoming(h);
    oldConnection.changeState('closed');
    assert.equal(h.state.call.currentCallId, 'incoming-1');
    assert.equal(h.state.call.mode, 'incoming');
});

test('decline closes immediately even if the signaling request is slow', async () => {
    const h = harness();
    incoming(h);
    const delivery = deferred();
    h.context.sendSignal = () => delivery.promise;
    const decline = h.context.rejectIncomingCall();
    await settle();
    assert.equal(h.state.call.mode, 'idle');
    delivery.resolve();
    await decline;
});

test('a rejected remote answer cleans up and informs the other participant', async () => {
    const h = harness();
    await h.context.startOutgoingCall();
    h.connections[0].setRemoteDescription = async () => { throw new Error('Invalid SDP'); };
    await h.context.handleCallSignal({ sender: { id: 2 }, signal: {
        call_id: h.state.call.currentCallId, signal_type: 'answer', sdp: { type: 'answer', sdp: 'invalid' },
    } });
    assert.equal(h.state.call.mode, 'idle');
    assert.equal(h.track.stopped, true);
    assert.equal(h.signals.at(-1).signal_type, 'hangup');
});

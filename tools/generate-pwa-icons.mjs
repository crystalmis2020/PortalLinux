import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

const outputDirectory = path.resolve('public/assets/images/pwa');
const pngSignature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);

const glyphs = {
    I: ['11111', '00100', '00100', '00100', '00100', '00100', '11111'],
    M: ['10001', '11011', '10101', '10101', '10001', '10001', '10001'],
    O: ['01110', '10001', '10001', '10001', '10001', '10001', '01110'],
    P: ['11110', '10001', '10001', '11110', '10000', '10000', '10000'],
    R: ['11110', '10001', '10001', '11110', '10100', '10010', '10001'],
    S: ['01111', '10000', '10000', '01110', '00001', '00001', '11110'],
    T: ['11111', '00100', '00100', '00100', '00100', '00100', '00100'],
    U: ['10001', '10001', '10001', '10001', '10001', '10001', '01110'],
};

const colors = {
    background: [2, 104, 30, 255],
    foreground: [255, 255, 255, 255],
    accent: [212, 222, 60, 255],
};

function createCanvas(size, color) {
    const pixels = Buffer.alloc(size * size * 4);

    for (let offset = 0; offset < pixels.length; offset += 4) {
        pixels[offset] = color[0];
        pixels[offset + 1] = color[1];
        pixels[offset + 2] = color[2];
        pixels[offset + 3] = color[3];
    }

    return pixels;
}

function fillRectangle(pixels, canvasSize, x, y, width, height, color) {
    const minX = Math.max(0, Math.floor(x));
    const minY = Math.max(0, Math.floor(y));
    const maxX = Math.min(canvasSize, Math.ceil(x + width));
    const maxY = Math.min(canvasSize, Math.ceil(y + height));

    for (let row = minY; row < maxY; row += 1) {
        for (let column = minX; column < maxX; column += 1) {
            const offset = (row * canvasSize + column) * 4;
            pixels[offset] = color[0];
            pixels[offset + 1] = color[1];
            pixels[offset + 2] = color[2];
            pixels[offset + 3] = color[3];
        }
    }
}

function textWidth(text, scale) {
    return ((text.length * 5) + Math.max(0, text.length - 1)) * scale;
}

function drawText(pixels, canvasSize, text, y, scale, color) {
    let x = Math.floor((canvasSize - textWidth(text, scale)) / 2);

    for (const character of text) {
        const glyph = glyphs[character];

        if (!glyph) {
            throw new Error(`Unsupported icon character: ${character}`);
        }

        glyph.forEach((row, rowIndex) => {
            Array.from(row).forEach((pixel, columnIndex) => {
                if (pixel === '1') {
                    fillRectangle(
                        pixels,
                        canvasSize,
                        x + columnIndex * scale,
                        y + rowIndex * scale,
                        scale,
                        scale,
                        color
                    );
                }
            });
        });

        x += 6 * scale;
    }
}

const crcTable = Array.from({ length: 256 }, (_, number) => {
    let value = number;
    for (let bit = 0; bit < 8; bit += 1) {
        value = (value & 1) ? 0xedb88320 ^ (value >>> 1) : value >>> 1;
    }
    return value >>> 0;
});

function crc32(buffer) {
    let crc = 0xffffffff;
    for (const byte of buffer) {
        crc = crcTable[(crc ^ byte) & 0xff] ^ (crc >>> 8);
    }
    return (crc ^ 0xffffffff) >>> 0;
}

function pngChunk(type, data) {
    const name = Buffer.from(type, 'ascii');
    const length = Buffer.alloc(4);
    const checksum = Buffer.alloc(4);
    length.writeUInt32BE(data.length);
    checksum.writeUInt32BE(crc32(Buffer.concat([name, data])));
    return Buffer.concat([length, name, data, checksum]);
}

function encodeRgbaPng(width, height, pixels) {
    const header = Buffer.alloc(13);
    header.writeUInt32BE(width, 0);
    header.writeUInt32BE(height, 4);
    header[8] = 8;
    header[9] = 6;

    const scanlines = Buffer.alloc(height * (1 + width * 4));
    for (let y = 0; y < height; y += 1) {
        const outputOffset = y * (1 + width * 4);
        scanlines[outputOffset] = 0;
        pixels.copy(scanlines, outputOffset + 1, y * width * 4, (y + 1) * width * 4);
    }

    return Buffer.concat([
        pngSignature,
        pngChunk('IHDR', header),
        pngChunk('IDAT', zlib.deflateSync(scanlines, { level: 9 })),
        pngChunk('IEND', Buffer.alloc(0)),
    ]);
}

function renderIcon(size) {
    const pixels = createCanvas(size, colors.background);
    const misScale = Math.max(1, Math.floor(size * 0.039));
    const supportScale = Math.max(1, Math.floor(size * 0.015625));
    const misHeight = 7 * misScale;
    const supportHeight = 7 * supportScale;
    const misY = Math.floor(size * 0.25);
    const dividerY = Math.floor(size * 0.60);
    const supportY = Math.floor(size * 0.69);

    drawText(pixels, size, 'MIS', misY, misScale, colors.foreground);
    fillRectangle(
        pixels,
        size,
        Math.floor(size * 0.30),
        dividerY,
        Math.floor(size * 0.40),
        Math.max(2, Math.floor(size * 0.018)),
        colors.accent
    );
    drawText(pixels, size, 'SUPPORT', supportY, supportScale, colors.foreground);

    if (misY + misHeight >= dividerY || supportY + supportHeight >= size * 0.88) {
        throw new Error('The generated icon text exceeded its safe area.');
    }

    return pixels;
}

fs.mkdirSync(outputDirectory, { recursive: true });

for (const size of [192, 512]) {
    fs.writeFileSync(
        path.join(outputDirectory, `icon-${size}.png`),
        encodeRgbaPng(size, size, renderIcon(size))
    );
}

console.log('Generated exact-text MIS Support PWA icons.');

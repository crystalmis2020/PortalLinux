@extends('design')
@section('content')
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">Limitations</h1>
    </div>

</div>
<!-- /.row -->
<div class="row">
    <div class="col-lg-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                Create Limitation
            </div>
            <div class="panel-body">
                {{Form::open(['url' => route('limitationStore'), 'method' => 'post'])}}
                    <div class="form-group">
                        <label>Name</label>
                        <input class="form-control" type="text" name="name">
                    </div>
                    <div class="form-group">
                        <label>IP Pool</label>
                        <select class="form-control" name="ip_pool">
                        @if($ip_pools)
                        @for($i=0;$i<count($ip_pools);$i++)
                            <option value="{{$ip_pools[$i]['name']}}">{{$ip_pools[$i]['name']}}</option>
                        @endfor
                        @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rate Limit</label>
                        <select class="form-control" name="rate_limit_rx">
                            <option value=""> - </option>
                            <option value="1048576">1M</option>
                            <option value="2097152">2M</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Burst Rate</label>
                        <select class="form-control" name="rate_limit_burst_rx">
                            <option value=""> - </option>
                            <option value="1048576">1M</option>
                            <option value="2097152">2M</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Burst Threshold Rate</label>
                        <select class="form-control" name="rate_limit_burst_treshold_rx">
                            <option value=""> - </option>
                            <option value="1048576">1M</option>
                            <option value="2097152">2M</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label >Burst Time</label>
                        <select class="form-control" name="rate_limit_burst_time_rx">
                            <option value=""> - </option>
                            <option value="60S">60 Sec</option><option value="59S">59 Sec</option><option value="58S">58 Sec</option><option value="57S">57 Sec</option><option value="56S">56 Sec</option><option value="55S">55 Sec</option><option value="54S">54 Sec</option><option value="53S">53 Sec</option><option value="52S">52 Sec</option><option value="51S">51 Sec</option>
                            <option value="50S">50 Sec</option><option value="49S">49 Sec</option><option value="48S">48 Sec</option><option value="47S">47 Sec</option><option value="46S">46 Sec</option><option value="45S">45 Sec</option><option value="44S">44 Sec</option><option value="43S">43 Sec</option><option value="42S">42 Sec</option><option value="41S">41 Sec</option>
                            <option value="40S">40 Sec</option><option value="39S">39 Sec</option><option value="38S">38 Sec</option><option value="37S">37 Sec</option><option value="36S">36 Sec</option><option value="35S">35 Sec</option><option value="34S">34 Sec</option><option value="33S">33 Sec</option><option value="32S">32 Sec</option><option value="31S">31 Sec</option>
                            <option value="30S">30 Sec</option><option value="29S">29 Sec</option><option value="28S">28 Sec</option><option value="27S">27 Sec</option><option value="26S">26 Sec</option><option value="25S">25 Sec</option><option value="24S">24 Sec</option><option value="23S">23 Sec</option><option value="22S">22 Sec</option><option value="21S">21 Sec</option>
                            <option value="20S">20 Sec</option><option value="19S">19 Sec</option><option value="18S">18 Sec</option><option value="17S">17 Sec</option><option value="16S">16 Sec</option><option value="15S">15 Sec</option><option value="14S">14 Sec</option><option value="13S">13 Sec</option><option value="12S">12 Sec</option><option value="11S">11 Sec</option>
                            <option value="10S">10 Sec</option><option value="9S">9 Sec</option><option value="8S">8 Sec</option><option value="7S">7 Sec</option><option value="6S">6 Sec</option><option value="5S">5 Sec</option><option value="4S">4 Sec</option><option value="3S">3 Sec</option><option value="2S">2 Sec</option><option value="1S">1 Sec</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Min Rate</label>
                        <select class="form-control" name="rate_limit_min_rx">
                            <option value=""> - </option>
                            <option value="1048576">1M</option>
                            <option value="2097152">2M</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select class="form-control" name="rate_limit_priority">
                            <option value="">Not Specified</option>
                            <option value="1">1 - Highest</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8 - Lowest</option>
                        </select>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-user-plus"></i> Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

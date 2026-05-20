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
                {{Form::open((['action' => ['ProfilesController@limitationUpdate'], 'method' => 'POST']))}}
                    <div class="form-group">
                        {{Form::label('name', 'Name')}}
                        {{Form::text('name', $limitation[0]['name'], ['class'=>'form-control'])}}
                    </div>
                    <div class="form-group">
                        <label>IP Pool</label>
                        <select class="form-control" name="ip_pool">
                        @if($ip_pools)
                        @for($i=0;$i<count($ip_pools);$i++)
                            <option value="{{$ip_pools[$i]['name']}}" {{ ($ip_pools[$i]['name'] == $limitation[0]['ip-pool']) ? 'selected' : '' }}>{{$ip_pools[$i]['name']}}</option>
                        @endfor
                        @endif
                        </select>
                    </div> <!--
                    <div class="form-group">
                        <label style="width:100%;float:left;">Rate Limit</label>
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_rx">
                            <option value=""> - </option>
                            <option value="1048576">RX 1M</option>
                            <option value="2097152">RX 2M</option>
                        </select>
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_tx">
                            <option value=""> - </option>
                            <option value="1048576">TX 1M</option>
                            <option value="2097152">TX 2M</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="width:100%;float:left;">Burst Rate</label>
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_burst_rx">
                            <option value=""> - </option>
                            <option value="1048576">RX 1M</option>
                            <option value="2097152">RX 2M</option>
                        </select>
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_burst_tx">
                            <option value=""> - </option>
                            <option value="1048576">TX 1M</option>
                            <option value="2097152">TX 2M</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="width:100%;float:left;">Burst Threshold Rate</label>
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_burst_treshold_rx">
                            <option value=""> - </option>
                            <option value="1048576">RX 1M</option>
                            <option value="2097152">RX 2M</option>
                        </select>                        
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_burst_treshold_tx">
                            <option value=""> - </option>
                            <option value="1048576">TX 1M</option>
                            <option value="2097152">TX 2M</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="width:100%;float:left;">Burst Time</label>
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_burst_time_rx">
                            <option value=""> - </option>
                            <option value="1048576">RX 1M</option>
                            <option value="2097152">RX 2M</option>
                        </select>
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_burst_time_tx">
                            <option value=""> - </option>
                            <option value="1048576">TX 1M</option>
                            <option value="2097152">TX 2M</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="width:100%;float:left;">Min Rate</label>
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_min_rx">
                            <option value=""> - </option>
                            <option value="1048576">RX 1M</option>
                            <option value="2097152">RX 2M</option>
                        </select>
                        <select class="form-control" style="width:50%;float:left;" name="rate_limit_min_tx">
                            <option value=""> - </option>
                            <option value="1048576">TX 1M</option>
                            <option value="2097152">TX 2M</option>
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
                    </div> -->
                    <div class="text-center">
                        {{Form::hidden('id', $limitation[0]['.id'])}}
                         {{Form::hidden('_method', 'PUT')}}
                        <button type="submit" class="btn btn-primary"><i class="fa fa-user-plus"></i> Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

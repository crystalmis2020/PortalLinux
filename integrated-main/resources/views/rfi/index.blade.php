@extends('rfi.design')
@section('content')
            <!-- page title area start -->
            <div class="page-title-area">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <div class="breadcrumbs-area clearfix">
                            <h4 class="page-title pull-left">Dashboard</h4>
                            <ul class="breadcrumbs pull-left">
                            <li><a href="{{route('requestDash')}}">Home</a></li>
                                <li><span>Request for Internet</span></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-6 clearfix">
                        <div class="user-profile pull-right">
                            <img class="avatar user-thumb" src="{{asset('images/rfi/author/avatar.png')}}" alt="avatar">
                            <h4 class="user-name dropdown-toggle" data-toggle="dropdown">{{Request::ip()}} <i class="fa fa-angle-down"></i></h4>
                           
                        </div>
                    </div>
                </div>
            </div>
           
            <div class="main-content-inner">
                    <div class="row">
                        <!-- table success start -->
                        <div class="col-lg-6 mt-5">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Unconsumed Access</h4>
                                    <p class="text-muted font-14 mb-4"><code>Try the access below. If you cant access the internet using the access below, please send a request</code></p>
                                    <div class="single-table">
                                        <div class="table-responsive">
                                            <table class="table text-center">
                                                <thead class="text-uppercase bg-success">
                                                    <tr class="text-white">
                                                        <th scope="col">Access</th>
                                                        <th scope="col">Hours</th>
                                                        <th scope="col">Consumed</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $n = 0; ?>
													@foreach($requests as $request)
													@if($request->accesses_id != NULL && $request->accesses->is_used == 'No')
													<?php $n++ ?>
													<tr>
														<td>{{@$request->accesses->username}}</td>
														<td>{{@$request->hours}}</td>
													<td>{{@$request->accesses->used_uptime}}</td>
													</tr>
													@endif
													@endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mt-5">
                            <div class="card">
                                <div class="card-body">
                                        @include('messages')
                                    {!! Form::open(['action' => 'rfi\RequestController@store', 'method' => 'POST', 'id'=>'requestForm']) !!}    
                                        <input type="hidden" name="ip" value="<?php echo $_SERVER['REMOTE_ADDR'];?>" />
                                        <h4 class="header-title">Request Form</h4>
                                        <p class="text-muted font-14 mb-4"></p>
                                        <div class="form-group">
                                            {{Form::label('name', 'Name', ['class' => 'form-control-label'])}}
                                            {{Form::text('name', '', ['class' => 'form-control', 'placeholder' => 'Your Complete Name'])}}
                                        </div>
                                        <div class="form-group">
                                            {{Form::label('hours', 'Hours', ['class' => 'form-control-label'])}}
                                            {{Form::select('hours', ['3h'=>'3', '8h'=>'8'],'3h', ['class'=>'form-control'])}}
                                        </div>
                                        <div class="form-group">
                                            {{Form::label('purpose','Purpose',['class'=>'form-control-label'])}}
                                            {{Form::textarea('purpose', '',['aria-label'=>'With textarea', 'class'=>'form-control','rows'=>'10'])}}
                                        </div>
                                        <div class="col-auto my-1">
                                            <input type="hidden" name="n" value="<?php echo $n;?>" />
											
                                          <button type="submit" class="btn btn-primary" id="requestBtn" >Send Request</button>
										   <!-- <i>We are doing maintenance on this system. Please try again later.</i> -->

                                            <!--<i>We are doing maintenance on this system. It may take for awhile to finish. For internet access, please pop up MIS personal (MIS-Abing or xonivre). Thank you</i> -->
											
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script type="application/javascript">
                    var form = document.getElementById('requestForm');

                    function submitForm(){
                        var button = document.getElementById('requestBtn');
                        button.innerText = 'Loading please wait..!';
                        button.disabled = true;
                    }

                    if(form.addEventListener){
                        form.addEventListener('submit',submitForm,false);
                    } else {
                        form.attachEvent('onsubmit',submitForm);
                    }
                    
                </script>
                 
 @endsection       
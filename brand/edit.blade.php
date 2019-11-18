@extends('layouts.admin')

@section('content')
<div class="pcoded-content">
	<div class="pcoded-inner-content">
		<!-- Main-body start -->
		<div class="main-body">
			<div class="page-wrapper">
				<!-- Page-header start -->
				<div class="page-header">
					<div class="row align-items-end">
						<div class="col-lg-8">
							<div class="page-header-title">
								<div class="d-inline">
									<h4>Edit Brand</h4>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="page-body">
					<div class="row">
						<div class="col-sm-12">
							<!-- Basic Inputs Validation start -->
							<div class="card">
								<div class="card-header">
									<span>All <code>*</code> Inputs are Field Required
									</span>
									@if (count($errors) > 0)
									<div class="alert alert-danger">
										<ul>
											@foreach ($errors->all() as $error)
											<li>{{ $error }}</li>
											@endforeach
										</ul>
									</div>
									@endif
								</div>
								<div class="flash-message">
									@foreach (['danger', 'warning', 'success', 'info'] as $msg)
									@if(Session::has('alert-' . $msg))

									<div class="alert alert-{{ $msg }}">{{ Session::get('alert-' . $msg) }} <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a></div>
									@endif
									@endforeach
								</div>  
								<div class="card-block">
									<form id="form"  method="post" action="{{route('brands.update',$brand->id)}}" enctype="multipart/form-data" novalidate="">
										@csrf
										@method('PATCH')
										<div class="panel-body">
											<div class="form-group">
												<label class="col-sm-6 control-label">Brand Name <span
													class="required">*</span></label>
													<div class="col-sm-9">
														<input type="text" value="{{$brand->brand_name}}" name="brand_name" class="form-control"
														required />
													</div>
												</div>

											 
							
													<div class="form-group ">
														<label class="col-sm-6 control-label">Upload Image
															 </label>
															<div class="col-sm-9">
																<input type="file" class="form-control form-control-file  onlyimages"  name="logo"    />	

																<img style="margin-top: 10px" class="img-100" src="{{$brand->logo}}">
																
															</div>

														</div>			

																<div class="col-sm-12">															
															<button type="submit" class="col-sm-3  please_wait btn btn-primary waves-effect waves-light m-r-10">Save</button>
																<button type="button" class=" reset1 btn btn-info waves-effect waves-light">Reset
																</button>
																<button onclick="location.href = '{{route('brands.index')}}';"type="button"  class="reset1 btn btn-inverse waves-effect waves-light m-b-0">Back</button>
															
														</div>
													</form>
												</div>
											</div>

										</div>
										<!-- Page-body end -->
									</div>
								</div>
					

				@endsection
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
                  <h4>Brand List</h4>

                </div>
              </div>
            </div>

          </div>
        </div>             

        <div class="page-body">
          <div class="row">
            <div class="col-sm-12">
              <!-- Zero config.table start -->
              <div class="card">
                <div class="card-header">
                 <div class="col-md-6 f-left"> <a class="btn btn-primary btn-add-task waves-effect waves-light m-t-10" href="{{route('brands.create')}}" ><i class="icofont icofont-plus"></i> Add New Brand</a>
                  <form id="deleteForm"  method="post" action="{{route('brands.deleteMultiple')}}" enctype="multipart/form-data" novalidate="">
                    @csrf
                    <input type="hidden" name="delete" id="deleteSeleted">
                    <button  type="button" id="deleteAll" class="col-sm-3  btn btn-danger waves-effect waves-light mg_8 ">Delete</button>
                  </form>
                </div>
                <div class="col-md-6 f-right">
                 <div class="flash-message">
                  @foreach (['danger', 'warning', 'success', 'info'] as $msg)
                  @if(Session::has('alert-' . $msg))

                  <div class="alert alert-{{ $msg }} ">{{ Session::get('alert-' . $msg) }} <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a></div>
                  @endif
                  @endforeach
                </div>
              </div>


            </div>
            <div class="card-block">
              <div class="dt-responsive table-responsive">
                <table id="simpletable" class="table table-striped table-bordered nowrap">
                  <thead>
                    <tr>
                     <th>ID</th>
                     <th>Name</th>                                           
                     <th data-orderable="false">Image</th>
                     <th data-orderable="false">Action</th>
                     <th data-orderable="false">
                       <div class="checkbox-fade fade-in-primary">
                        <label>
                          <input  id="checkAll" type="checkbox"   >
                          <span class="cr">
                            <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                          </span>
                        </label>
                      </div> 
                    </th>
                  </tr>
                </thead>
                <tbody>
                  @if(!empty($brands))
                  @foreach($brands as  $brand)
                  <tr>
                    <td>{{$brand->id}}</td>
                    <td>{{$brand->brand_name}}</td>
                    <td><img class="img-50" src="{{$brand->logo}}"></td>

                    <td>
                      <a href="{{route('brands.edit',$brand->id)}}" class="btn btn-info btn_iedit"  data-placement="top" title="" data-original-title="Edit">
                        <i class="fa fa-edit"></i></a>

                        <form action="{{ route('brands.destroy', $brand->id)}}" method="post">
                          @csrf
                          @method('DELETE')
                          <button onclick="return confirm('Are you sure you want to delete?');" class="btn btn-danger btn_iedit" type="submit"><i class="fa fa-trash"></i> </button>
                        </form>
                      </td>
                        
                      <td> 
                       <div class="checkbox-fade fade-in-primary">
                        <label>
                          <input  class="checkBoxClass" type="checkbox"  name="delete[]" value="{{$brand->id}}">
                          <span class="cr">
                            <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                          </span>

                        </label>
                      </div>
                    </td>

                  </tr>
                  @endforeach
                  @endif
                </tbody>

              </table>
            </div>
          </div>
        </div>
        <!-- Language - Comma Decimal Place table end -->
      </div>
    </div>
  </div>
  <!-- Page-body end -->
</div>
</div> 
</div>
</div>
@endsection
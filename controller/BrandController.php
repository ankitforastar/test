<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Repositories\BrandRepo;
use session;
class BrandController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     public function __construct()
    {   $this->middleware('role:ADMIN');
       $this->middleware('auth');
    }
    public function index()
    {


        $brands = BrandRepo::getAll();       
        return view('admin.brand.list',compact('brands'));
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
       return view('admin.brand.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BrandRequest $request)
    {
        $input = $request->all();

        if($request->hasFile('logo')) {
            $file=$request->file('logo');            
            $size= MEDIUM;
            $path='brand';
            $data=  $this->singleImageUpload($file,$size,$path);        
            $input['logo'] = $data['original'];     
        }        
        BrandRepo::create($input);        
        $request->session()->flash('alert-success', 'Brand added successfully!');
        return redirect()->route('brands.index');
    }


    public function edit($id)
    {
        $brand = BrandRepo::getById($id);        
        return view('admin.brand.edit',compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(BrandRequest $request, $id)
    {

        $input = $request->all();
        if ($request->hasFile('logo')) {
            $file=$request->file('logo');
            $size= MEDIUM;
            $path='brand';
            $data=  $this->singleImageUpload($file,$size,$path);        
            $input['logo'] = $data['original']; 
            $brand = BrandRepo::getById($id);
            $this->removeFiles(array($brand->logo));
        }   



        BrandRepo::udpate($input,$id); 
        $request->session()->flash('alert-success', 'Brand updated successfully!');
        return redirect()->route('brands.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {      
        BrandRepo::delete($id);  
        session()->flash('alert-success', 'Brand successfully deleted!');
        return redirect()->route('brands.index');
    }
    public function deleteMultiple(Request $request)
    {

      $input = $request->all();
      $delete = array_filter(explode(',', $input['delete']));    
      if(empty( $delete)){
         session()->flash('alert-danger', 'Please select atleast one checkbox!');
         return redirect()->back();
     }
     BrandRepo::deleteMultiple($delete);  
     session()->flash('alert-success', 'Brand successfully deleted!');
     return redirect()->back();
 }
}

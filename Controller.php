<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Image;  
use Mail;
use App\Models\Notification;
use Illuminate\Support\Facades\Storage;  
use Carbon\Carbon;
use PHPMailer;
class Controller extends BaseController
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;



	public function singleImageUpload($file,$size=null,$path=null){

		$filenamewithextension = $file->getClientOriginalName();
		$filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
		$extension = $file->getClientOriginalExtension();
		$filenametostore = str_slug($filename).'_'.time().uniqid().'.'.$extension;
		//customize path as per month year
		$path = $path.'/'.date('Y').'/'.date('m');		
        //file store to storage
		$original_file_path = $file->storeAs($path, $filenametostore);
		$thumb_file_path = $file->storeAs($path.'/modified', $filenametostore);
        //Resize image here
		$thumbnailpath = storage_path('app/'.$path.'/modified/'.$filenametostore);
		$img = Image::make($thumbnailpath)->fit($size);
		$img->save($thumbnailpath);
		$files= array(
			'original'=>$original_file_path,
			'modified'=>$thumb_file_path);
		return $files;
	}


	public function multipleImageUpload($files,$size=null,$path=null){
		$multipleImg = array();
		$path = $path.'/'.date('Y').'/'.date('m');	
		foreach($files as $file){
			$filenamewithextension =$file->getClientOriginalName();
			$filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
			$extension =$file->getClientOriginalExtension();
			$filenametostore = str_slug($filename).'_'.time().uniqid().'.'.$extension;
		    //customize path as per month year
			
            //file store to storage
			$original_file_path = $file->storeAs($path, $filenametostore);
			$thumb_file_path = $file->storeAs($path.'/modified', $filenametostore);
            //Resize image here
			$thumbnailpath = storage_path('app/'.$path.'/modified/'.$filenametostore);
			$img = Image::make($thumbnailpath)->fit($size);
			// $img = Image::make($thumbnailpath)->resize($size, null, function($constraint) {
			// 	$constraint->aspectRatio();
			// });
			$img->save($thumbnailpath);
			
			$multipleImg[] = array(
				'file_path'=>$original_file_path,
				'file_path_modified'=>$thumb_file_path
			);
		}		
		return $multipleImg;
	}
	public function filesUpload($files,$path=null){
		$path = $path.'/'.date('Y').'/'.date('m');	
		$multipleFiles = array();
		foreach($files as $file){
			$filenamewithextension =$file->getClientOriginalName();
			$filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
			$extension =$file->getClientOriginalExtension();
			$filenametostore = $filename.'_'.time().uniqid().'.'.$extension;
		    //customize path as per month year
			
            //file store to storage
			$original_file_path = $file->storeAs($path, $filenametostore);			
            //Resize image here			
			$multipleFiles[] = array(
				'file'=>$original_file_path
			);
		}		
		return $multipleFiles;
	}

	// For delete multiple files
	public function removeFiles($paths=null){
		foreach($paths as $path){			
			$delete_file = str_replace(url('storage/app'),"",$path);
			Storage::delete($delete_file);
		}
	}

	public function get_times( $default = '19:00', $interval = '+30 minutes' ) {

		$output = '';

		$current = strtotime( '00:00' );
		$end = strtotime( '23:59' );

		while( $current <= $end ) {
			$time = date( 'H:i', $current );
			$sel = ( $time == $default ) ? ' selected' : '';

			$output .= "<option value=\"{$time}\"{$sel}>" . date( 'h.i A', $current ) .'</option>';
			$current = strtotime( $interval, $current );
		}

		return $output;
	}

	
	/** FOR API creations
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
	public function sendResponse($result, $message)
	{

		if($result!=NULL){
			$result = $result;
		}else{
			$result = [];
		}
		$response = [
			'success' => true,
			'data'    => $result,
			'message' => $message,
		];

		return response()->json($response, 200);
	
  }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 200)
    {
    	$response = [
    		'success' => false,
    		'message' => $error,
    	];


    	if(!empty($errorMessages)){
    		$response['data'] = $errorMessages;
    	}


    	return response()->json($response, $code);
    }
    public function messageResponse($message)
    {


    	$response = [
    		'success' => true,    		
    		'message' => $message,
    	];


    	return response()->json($response, 200);
    }


 // Insert Notification
    public function notifiy($input){
    	
    	return	Notification::create($input);
    }
    public function notifiyAdmin($input){    

    	return	Notification::create($input);
    }


    public function sendMail($sendTo=null,$subject=null,$body=null){
    	require_once(base_path()."/class.phpmailer.php");

  $mail = new PHPMailer(true); //New instance, with exceptions enabled
//From email address and name


$mail->From = EMAIL;
$mail->FromName = EMAILNAME;

//To address and name


$mail->addAddress($sendTo['email'], $sendTo['name']);
//$mail->addCC("tptankit@gmail.com","Test "); //Recipient name is optional

//Address to which recipient will reply
 // $mail->addReplyTo("reply@yourdomain.com", "Reply");

//CC and BCC
 // $mail->addCC("cc@example.com");
 // $mail->addBCC("bcc@example.com");

//Send HTML or Plain Text email
$mail->isHTML(true);

$mail->Subject = $subject;
$mail->Body = $body;
  //$mail->AltBody = "This is the plain text version of the email content";
return $mail->send();

}
public function sendMailBulk($sendTo=null,$subject=null,$body=null){
	require_once(base_path()."/class.phpmailer.php");

  $mail = new PHPMailer(true); //New instance, with exceptions enabled
//From email address and name
  $mail->From = EMAIL;
  $mail->FromName = EMAILNAME;

//To address and name
  if(!empty($sendTo)){
  	foreach($sendTo as $To){
  		$mail->addAddress($To, '');
  		$mail->addCC($To, '');
  		$mail->addBCC($To, '');
  	}
  }
  
//$mail->addAddress("recepient1@example.com"); //Recipient name is optional

//Address to which recipient will reply
 // $mail->addReplyTo("reply@yourdomain.com", "Reply");

//CC and BCC
 // $mail->addCC("cc@example.com");
 // $mail->addBCC("bcc@example.com");

//Send HTML or Plain Text email
  $mail->isHTML(true);

  $mail->Subject = $subject;
  $mail->Body = $body;
  //$mail->AltBody = "This is the plain text version of the email content";
  return $mail->send();
  // if(!$mail->send()) 
  // {
  // 	echo "Mailer Error: " . $mail->ErrorInfo;
  // } 
  // else 
  // {
  // 	echo "Message has been sent successfully";
  // }
}
 





    }

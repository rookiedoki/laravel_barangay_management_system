<?php

namespace App\Http\Controllers;



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


use Illuminate\Support\Facades\Storage;

use App\Models\PermitTemplate;
use App\Models\PermitFees;
use App\Models\PermitType;
use App\Models\PermitHistory;
use App\Models\PermitStatus;



class PermitRequestController extends Controller
{

    public function generatePermit(Request $request){

        $this->validateGeneratePermit($request);


      /*   $templateData = PermitTemplate::find($request->template_id);

        if(empty($templateData)){
            return response()->json([
                'error' => 'invalid',
                'message' => "Template not found."
            ], 400);
        }

        $typeData = PermitType::find($request->permit_type_id);
        if(empty($typeData)){
            return response()->json([
                'error' => 'invalid',
                'message' => "Permit type not found."
            ], 400);
        }

        $feeData = PermitFees::find($request->permit_fee_id);
        if(empty($feeData)){
            return response()->json([
                'error' => 'invalid',
                'message' => "Permit Fee not found."
            ], 400);
        } */

        $controlNumber = $this->getControlNumber();
        if(!empty($request['payment_image']) && !empty($request['reference_number'])){
            $status = PermitStatus::FOR_APPROVAL_STATUS;
        }else{
            $status = PermitStatus::FOR_PAYMENT_STATUS;
        }

        $path = "";
        $imageName = "";
        if($request->hasFile('payment_image') && !empty($request['reference_number'])){
            $path = 'public/images/permit/payment';

            $image = $request->file('payment_image');
            $imageName = $image->getClientOriginalName();

            $request->file('payment_image')->storeAs($path,$imageName);
        }
        #$status = 1; //# for approval
        $data = [
           # 'template_id' => $request->template_id,
            'permit_type_id' => $request->permit_type_id,
            'category_id' => $request->category_id,
            'barangay_id' => $request->barangay_id,
            'control_number' => $controlNumber,
            'status_id' => $status,
            'user_id' => $request->user_id,
            'payment_method_id' => $request->payment_method_id,
            'file_name' => $imageName,
            'file_path' => $path,
            'is_waive' => !empty($request['is_waive']) ? 1:  0 ,
            'waive_reason' => $request['reason_for_waving']
        ];
        PermitHistory::create($data);




        return customResponse()
            ->data(null)
            ->message("Permit generated.")
            ->success()
            ->generate();
    }

    private function validateGeneratePermit($request){

        $validator = Validator::make($request->all(),[
         #   'template_id' => 'required|integer|min:0',
            'barangay_id' => 'required|integer|min:0',
            'permit_type_id' => 'required|integer|min:0',
            'category_id' => 'required|integer|min:0',
            'user_id' => 'required|integer|min:0',
            'payment_method_id' => 'required|integer|min:0',
            'payment_image' => 'mimes:jpg,bmp,png,pdf,txt,doc,docx',


        ]);

        if($validator->fails()){
            return customResponse()
            ->data(null)
            ->message($validator->errors()->all()[0])
            ->failed()
            ->generate();
        }
    }

    public function permitPayment(Request $request){


        $validator = Validator::make($request->all(),[
            'payment_file' => 'mimes:jpg,bmp,png,pdf,txt,doc,docx',
            'id' => 'required|integer|min:0',
        ]);

        if($validator->fails()){
            return customResponse()
            ->data(null)
            ->message($validator->errors()->all()[0])
            ->failed()
            ->generate();
        }

        $path = "";
        $imageName = "";

        if($request->hasFile('payment_file') && !empty($request['reference_number'])){

            $path = 'public/images/permit/payment';

            $image = $request->file('payment_file');
            $imageName = $image->getClientOriginalName();

            $request->file('payment_file')->storeAs($path,$imageName);

            $status = PermitStatus::FOR_APPROVAL_STATUS;
            $historyData = PermitHistory::find($request['id']);
            $historyData->file_path = $path;
            $historyData->file_name = $imageName;
            $historyData->status_id = $status;
            $historyData->reference_number = $request['reference_number'];
            $historyData->save();
            return customResponse()
            ->data(null)
            ->message("Permit payment success.")
            ->success()
            ->generate();
        }

    }



    private function getControlNumber(){
        return rand();
    }


    public function list(Request $request){

        $historyData = PermitHistory::where("user_id","!=","");

        if(!empty($request['barangay_id'])){
            $historyData = $historyData->where("barangay_id",$request['barangay_id']);
        }
        if(!empty($request['category_id'])){
            $historyData = $historyData->where("category_id",$request['category_id']);
        }
        if(!empty($request['permit_type_id'])){
            $historyData = $historyData->where("permit_type_id",$request['permit_type_id']);
        }
        if(!empty($request['user_id'])){
            $historyData = $historyData->where("user_id",$request['user_id']);
        }

        $historyData = $historyData->with('category','barangay','permitType','user','paymentMethod','status')->get();

        $return = array();
        foreach($historyData as $row){

            $userFullName = $row->user->first_name.' '. $row->user->middle_name.' '. $row->user->last_name;
            $return[] = array(
                'id' => $row->id,
                'category' => $row->category->description,
                'barangay' => $row->barangay->description,
                'permit_type' => $row->permitType->permit_name,
                'user' => $userFullName,
                'payment_method' => $row->paymentMethod->description,
                'status' => $row->status->description,
                'release_date' => $row->release_date,

            );

        }

        return customResponse()
            ->data($return)
            ->message("Permit request list.")
            ->success()
            ->generate();

    }
}

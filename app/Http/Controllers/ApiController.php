<?php

namespace App\Http\Controllers;

use App\Client;
use App\Domain\GlobalDbRecordCounter;
use App\Domain\GlobalDtoValidator;
use App\Domain\GlobalResultHandler;
use App\Domain\Model\ClientServiceValidity;
use App\Jobs\ProccessMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ApiController extends Controller
{

    public function registerUserFromIdentitiesAndAccess(){

        $dataJson = file_get_contents('php://input');
        $dataArray =  json_decode($dataJson, true);

        if(!$dataArray){
            return response(GlobalResultHandler::buildFaillureReasonArray("Invalid Data"), 200);
        }


        $validationrules =  [
            'userid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'tenantid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'firstname' => GlobalDtoValidator::requireStringMinMax(1, 100),
            'lastname' => GlobalDtoValidator::requireStringMinMax(1, 100),
        ];

        $validator = GlobalDtoValidator::validateData($dataArray, $validationrules) ;


        if ($validator->fails()) {

            return response(GlobalResultHandler::buildFaillureReasonArray($validator->errors()->first()), 200);

        }


        $clientToRegister = new Client(
            $dataArray['userid'],
            $dataArray['tenantid'],
            $dataArray['firstname'],
            $dataArray['lastname']
        );



        DB::beginTransaction();

        try{

            $clientToRegister->save();

        }catch (\Exception $e){

            DB::rollBack();


            return response(GlobalResultHandler::buildFaillureReasonArray('Unable to register Client'), 200);

        }

        DB::commit();

        return response(GlobalResultHandler::buildSuccesResponseArray('Client registered Successfully'), 200);

    }

    public function createOrUpdateClientServiceValidity(){

        $dataJson = file_get_contents('php://input');
        $dataArray =  json_decode($dataJson, true);


        if(!$dataArray){
            return response(GlobalResultHandler::buildFaillureReasonArray("Invalid Data"), 200);
        }



        $validationrules =  [
            'serviceid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'userid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'tenantid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'period' => GlobalDtoValidator::requireNumeric(),
        ];



        if (GlobalDtoValidator::validateData($dataArray, $validationrules)->fails()) {
            return response(GlobalResultHandler::buildFaillureReasonArray(GlobalDtoValidator::validateData($dataArray, $validationrules)->errors()->first()), 200);
        }



        DB::beginTransaction();

        try{

            $clientsToAddValidityTo = Client::where('clientid', '=', $dataArray['userid'])->get();


            if(!GlobalDbRecordCounter::countDbRecordIsExactlelOne($clientsToAddValidityTo)){return response(GlobalResultHandler::buildFaillureReasonArray('Client not found'), 200);}



            $checkIfServiceWasRegisteredForClients = ClientServiceValidity::where('serviceid', '=', $dataArray['serviceid'] )->where('clientid', '=', $dataArray['userid'] )->where('tenantid', '=', $dataArray['tenantid'] )->get();


            $nowDateTimeStamp = time();



            if(GlobalDbRecordCounter::countDbRecordIsExactlelOne($checkIfServiceWasRegisteredForClients)){




                $checkIfServiceWasRegisteredForClient = $checkIfServiceWasRegisteredForClients[0];







                if(!(($checkIfServiceWasRegisteredForClient->startdate <  $nowDateTimeStamp ) and ($nowDateTimeStamp < $checkIfServiceWasRegisteredForClient->enddate))){


                    $checkIfServiceWasRegisteredForClient->startdate = $nowDateTimeStamp;
                    $checkIfServiceWasRegisteredForClient->enddate = $nowDateTimeStamp + $dataArray['period'];

                    $checkIfServiceWasRegisteredForClient->save();


                }else {


                    $checkIfServiceWasRegisteredForClient->enddate += $dataArray['period'];;

                    $checkIfServiceWasRegisteredForClient->save();


                }


            }else{

                $newServiceValidityForClient = new ClientServiceValidity($dataArray['serviceid'], $dataArray['userid'], $dataArray['tenantid'], $nowDateTimeStamp,  $nowDateTimeStamp + $dataArray['period'], true );

                $newServiceValidityForClient->save();


            }

        }catch (\Exception $e){

            DB::rollBack();

            return response(GlobalResultHandler::buildFaillureReasonArray($e->getMessage()), 200);

        }

        DB::commit();


        return response(GlobalResultHandler::buildSuccesResponseArray(date('Y-m-d', $checkIfServiceWasRegisteredForClient->startdate).' '.date('Y-m-d', $checkIfServiceWasRegisteredForClient->enddate)), 200);


    }



}

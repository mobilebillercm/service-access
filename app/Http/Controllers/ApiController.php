<?php

namespace App\Http\Controllers;

use App\Client;
use App\Domain\GlobalDbRecordCounter;
use App\Domain\GlobalDtoValidator;
use App\Domain\GlobalResultHandler;
use App\Domain\Model\ClientServiceValidity;
use App\Jobs\ProccessMessage;
use App\Unit;
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


        $validationrules =  [
            'service' => GlobalDtoValidator::required(),
            'user' => GlobalDtoValidator::required(),
            'userid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'tenantid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'price' => GlobalDtoValidator::required(),
        ];



        if (GlobalDtoValidator::validateData($dataArray, $validationrules)->fails()) {
            return response(GlobalResultHandler::buildFaillureReasonArray(GlobalDtoValidator::validateData($dataArray, $validationrules)->errors()->first()), 200);
        }



        $service = json_decode($dataArray['service'], true);
        //$user = json_decode($dataArray['user'], true);
        $tenantid = $dataArray['tenantid'];
        $price = json_decode($dataArray['price'], true);
        $timeunit = $price['duration']['timeUnit'];
        $value = $price['duration']['value'];
        $seviceid = $service['b_id'];



        $unitmultipliers = Unit::where('name', '=', $timeunit)->get();



        //return json_encode($unitmultipliers);

        if(!GlobalDbRecordCounter::countDbRecordIsExactlelOne($unitmultipliers)){
            return response(GlobalResultHandler::buildFaillureReasonArray("Time unit not found"), 200);
        }


        $unitmultiplier = $unitmultipliers[0]->numdays;
        $period =(int)$unitmultiplier * (int)$value * 3600 * 24;

        //return $period;


        if(!$dataArray){
            return response(GlobalResultHandler::buildFaillureReasonArray("Invalid Data"), 200);
        }






        DB::beginTransaction();

        try{



            $clientsToAddValidityTo = Client::where('clientid', '=', $dataArray['userid'])->where('tenantid', '=', $tenantid)->get();

            //return json_encode($clientsToAddValidityTo);


            if(!GlobalDbRecordCounter::countDbRecordIsExactlelOne($clientsToAddValidityTo)){return response(GlobalResultHandler::buildFaillureReasonArray('Client not found'), 200);}



            $checkIfServiceWasRegisteredForClients = ClientServiceValidity::where('serviceid', '=', $seviceid)->where('clientid', '=', $dataArray['userid'] )->where('tenantid', '=', $tenantid)->get();


            $nowDateTimeStamp = time();


            $existingservice = false;



            if(GlobalDbRecordCounter::countDbRecordIsExactlelOne($checkIfServiceWasRegisteredForClients)){



                $existingservice = true;

                $checkIfServiceWasRegisteredForClient = $checkIfServiceWasRegisteredForClients[0];







                if(!(($checkIfServiceWasRegisteredForClient->startdate <  $nowDateTimeStamp ) and ($nowDateTimeStamp < $checkIfServiceWasRegisteredForClient->enddate))){


                    $checkIfServiceWasRegisteredForClient->startdate = $nowDateTimeStamp;
                    $checkIfServiceWasRegisteredForClient->enddate = $nowDateTimeStamp + $period;

                    $checkIfServiceWasRegisteredForClient->save();


                }else {



                    $checkIfServiceWasRegisteredForClient->enddate += $period;;

                    $checkIfServiceWasRegisteredForClient->save();


                }


            }else{

                $existingservice = false;

                $newServiceValidityForClient = new ClientServiceValidity($seviceid, $dataArray['userid'], $tenantid,
                    $nowDateTimeStamp,  $nowDateTimeStamp + $period, 1, 0, null );


                $newServiceValidityForClient->save();


            }

        }catch (\Exception $e){

            DB::rollBack();

            return response(GlobalResultHandler::buildFaillureReasonArray($e->getMessage()), 200);

        }

        DB::commit();


        if($existingservice){
            ProccessMessage::dispatch(env('SERVICE_ACCESS_REGISTERED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($checkIfServiceWasRegisteredForClient));
        }else{
            ProccessMessage::dispatch(env('SERVICE_ACCESS_REGISTERED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($newServiceValidityForClient));
        }



        return response(GlobalResultHandler::buildSuccesResponseArray(date('Y-m-d', $checkIfServiceWasRegisteredForClient->startdate).' '.date('Y-m-d', $checkIfServiceWasRegisteredForClient->enddate)), 200);


    }



}

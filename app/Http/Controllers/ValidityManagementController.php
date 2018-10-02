<?php

namespace App\Http\Controllers;



use App\Client;
use App\Domain\GlobalDbRecordCounter;
use App\Domain\GlobalDtoValidator;
use App\Domain\GlobalResultHandler;
use App\Domain\Model\ClientServiceValidity;
use App\Jobs\ProccessMessage;
use Illuminate\Http\Request;

class ValidityManagementController extends Controller
{
    public function disableClientServiceValidity(Request $request){

        $validationrules = [
            'serviceid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'tenantid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'clientid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'reason' => GlobalDtoValidator::requireStringMinMax(1, 1000),
        ];


        if (GlobalDtoValidator::validateData($request->all(), $validationrules)->fails()) {return response(GlobalResultHandler::buildFaillureReasonArray(GlobalDtoValidator::validateData($request->all(), $validationrules)->errors()->first()), 200);}


        $checkIfServiceWasRegisteredForClients = ClientServiceValidity::where('serviceid', '=', $request->get('serviceid'))->where('clientid', '=', $request->get('clientid'))->where('tenantid', '=', $request->get('tenantid'))->get();


        if(GlobalDbRecordCounter::countDbRecordIsExactlelOne($checkIfServiceWasRegisteredForClients)) {

            $checkIfServiceWasRegisteredForClient = $checkIfServiceWasRegisteredForClients[0];

            if(!($checkIfServiceWasRegisteredForClient->enablementstatus)) {return response(GlobalResultHandler::buildFaillureReasonArray('The Client Service Validity is already disabled'), 200);}
            else {$checkIfServiceWasRegisteredForClient->enablementstatus = false; $checkIfServiceWasRegisteredForClient->reasonenablementchanged = $request->get('reason');   $checkIfServiceWasRegisteredForClient->save();}

        }



        ProccessMessage::dispatch(env('CLIENT_SERVICE_VALIDITY_DISABLED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($checkIfServiceWasRegisteredForClient));


        return response(GlobalResultHandler::buildFaillureReasonArray('The Client Service Validity was disabled successfully'), 200);


    }

    public function renableClientServiceValidity(Request $request){


        $validationrules = [
            'serviceid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'tenantid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'clientid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'reason' => GlobalDtoValidator::requireStringMinMax(1, 1000),

        ];


        if (GlobalDtoValidator::validateData($request->all(), $validationrules)->fails()) {return response(GlobalResultHandler::buildFaillureReasonArray(GlobalDtoValidator::validateData($request->all(), $validationrules)->errors()->first()), 200);}


        $checkIfServiceWasRegisteredForClients = ClientServiceValidity::where('serviceid', '=', $request->get('serviceid'))->where('clientid', '=', $request->get('clientid'))->where('tenantid', '=', $request->get('tenantid'))->get();


        if(GlobalDbRecordCounter::countDbRecordIsExactlelOne($checkIfServiceWasRegisteredForClients)) {

            $checkIfServiceWasRegisteredForClient = $checkIfServiceWasRegisteredForClients[0];

            if(($checkIfServiceWasRegisteredForClient->enablementstatus)) {return response(GlobalResultHandler::buildFaillureReasonArray('The Client Service Validity is already enabled'), 200);}
            else {$checkIfServiceWasRegisteredForClient->enablementstatus = true; $checkIfServiceWasRegisteredForClient->reasonenablementchanged = $request->get('reason'); $checkIfServiceWasRegisteredForClient->save();}

        }

        ProccessMessage::dispatch(env('CLIENT_SERVICE_VALIDITY_RENABLED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($checkIfServiceWasRegisteredForClient));


        return response(GlobalResultHandler::buildFaillureReasonArray('The Client Service Validity was renabled successfully'), 200);

    }

    public function isClientServiceValidityValid(Request $request){


        $validationrules = [
            'serviceid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'tenantid' => GlobalDtoValidator::requireStringMinMax(1, 150),
            'clientid' => GlobalDtoValidator::requireStringMinMax(1, 150),
        ];


        if (GlobalDtoValidator::validateData($request->all(), $validationrules)->fails()) {return response(GlobalResultHandler::buildFaillureReasonArray(GlobalDtoValidator::validateData($request->all(), $validationrules)->errors()->first()), 200);}


        $clientsToAddValidityTo = Client::where('clientid', '=', $request->get('clientid'))->get();


        if(!GlobalDbRecordCounter::countDbRecordIsExactlelOne($clientsToAddValidityTo)){return response(GlobalResultHandler::buildFaillureReasonArray('Client not found'), 200);}


        $checkIfServiceWasRegisteredForClients = ClientServiceValidity::where('serviceid', '=',  $request->get('serviceid') )->where('clientid', '=',  $request->get('clientid') )->where('tenantid', '=',  $request->get('tenantid') )->get();


        if(GlobalDbRecordCounter::countDbRecordIsExactlelOne($checkIfServiceWasRegisteredForClients)){

            $checkIfServiceWasRegisteredForClient = $checkIfServiceWasRegisteredForClients[0];



            $nowDateTimeStamp =  time();

            if((($checkIfServiceWasRegisteredForClient->startdate <  $nowDateTimeStamp ) and
                ($nowDateTimeStamp < $checkIfServiceWasRegisteredForClient->enddate and
                    ($checkIfServiceWasRegisteredForClient->enablementstatus == 1)))){


                return GlobalResultHandler::buildSuccesResponseArray(true);


            }else {

                return GlobalResultHandler::buildSuccesResponseArray(array('enanblement'=>false, 'reason'=>'Service Validity Expired'));


            }


        }else{

            return GlobalResultHandler::buildSuccesResponseArray(array('enanblement'=>false, 'reason'=>'Service not found') );


        }



    }
}

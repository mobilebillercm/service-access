<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Client extends Model
{


    protected $table = 'clients';

    protected $fillable = ['clientid', 'tenantid', 'firstname', 'lastname'];


    public function __construct($clientid = null, $tenantid = null, $firstname = null, $lastname = null, $attributes = array())
    {
        parent::__construct($attributes);
        $this->clientid = $clientid;
        $this->tenantid = $tenantid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }


}

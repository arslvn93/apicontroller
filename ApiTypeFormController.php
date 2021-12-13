<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\OfferRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiTypeFormController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function webhook(Request $request)
    {
        $data = $request->all();

        $form_response = $data['form_response'];

        if(!isset($form_response['hidden'])) return response()->json(['status'=>'failed']);

        $is_demo =  $form_response['hidden']['demo'] ?? false;
        if($is_demo)
            $user_id = User::select('id')->where('email','demo@support.ltd')->first()->id;
        else
            $user_id =  $form_response['hidden']['user_id'] ?? null;


        $form_id =  $form_response['form_id'];
        $answers = $form_response['answers'];

        $map = [
            'form_id' => $form_id
        ];


        foreach ($answers as $answer) {
            if($answer['type'] === 'choice') {
                $map[$answer['field']['ref']] = $answer['choice']['label'] ?? $answer['choice']['other'] ?? null;
            }
            if($answer['type'] === 'text')
                $map[$answer['field']['ref']] = $answer['text'];
            if($answer['type'] === 'email')
                $map[$answer['field']['ref']] = $answer['email'];
            if($answer['type'] === 'date')
                $map[$answer['field']['ref']] = $answer['date'];
            if($answer['type'] === 'number')
                $map[$answer['field']['ref']] = $answer['number'];
            if($answer['type'] === 'choices') {
                $other_option = isset($answer['choices']['other']) ? [$answer['choices']['other']] : [];
                $map[$answer['field']['ref']] = array_merge($answer['choices']['labels'] ?? [], $other_option);
            }
        }


        //we have to deal with this weird case since typeform doesn't allow
        //setting the same field name in different forms so this is a hacky way to
        //figure out what fields are across diffrent forms
        foreach ($map as $key => $val) {
            $key_chunks = explode("_",$key);
            if(in_array('mls',$key_chunks)) {
                $map['mls_number'] = preg_replace('/[^A-Za-z0-9\-]/', '', $val);
                unset($map[$key]);
                continue;
            }
            if(in_array('names',$key_chunks)) {
                $map['names'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('possession',$key_chunks)) {
                $map['possession'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('offer',$key_chunks)) {
                $map['offer_price'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('deposit',$key_chunks)) {
                $map['deposit'] = $val;
                continue;
            }
            if(in_array('email',$key_chunks)) {
                $map['email'] = $val;
                continue;
            }
            if(in_array('finance',$key_chunks)) {
                $map['finance_condition'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('status',$key_chunks)) {
                $map['status_review_condition'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('inspection',$key_chunks)) {
                $map['inspection_condition'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('level',$key_chunks)) {
                $map['legal_level'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('unit',$key_chunks)) {
                $map['legal_unit_number'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('parking',$key_chunks)) {
                $map['legal_parking_number'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('locker',$key_chunks)) {
                $map['legal_locker_number'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('chattels',$key_chunks)) {
                $map['chattels'] = $val;
                unset($map[$key]);
                continue;
            }
            if(in_array('legal',$key_chunks)) {
                $map['legal_description'] = $val;
                unset($map[$key]);
                continue;
            }
        }

        if($is_demo && !isset($map['email'])) return response()->json(['status'=>'failed']);


        //Log::info($map);

        OfferRequest::create([
            'mls_number' => $map['mls_number'],
            'user_id' => $user_id,
            'submission' => $map,
            'is_demo' => $is_demo ? 1 : 0
         ]);

        return response()->json(['status'=>'success']);

    }

}

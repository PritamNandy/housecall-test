<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\UserMedicine;
use Auth;
use Illuminate\Support\Facades\Cache;
use App\Repositories\CacheRepository;

class DrugController extends Controller
{
    protected $cacheRepository;

    public function __construct(CacheRepository $cacheRepository)
    {
        ini_set('max_execution_time', 1200);
        $this->cacheRepository = $cacheRepository;
    }

    function getDrugs(Request $request) {
        $validate = Validator::make($request->all(), [
            'drug_name' => 'required'
        ]);

        if($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->messages()
            ], 422);
        }

        $name = $request->get('drug_name');
        $url = env('drug_domain').'/REST/drugs.json?name='.$name;
        $result = Cache::get('drugs-'.$name);
        if(!$result) {
            $result = $this->cacheRepository->cache_drugs($url, $name);
        }
// die();
        //$result = json_decode($response->body());
        $drugs = array();

        if($result) {
            foreach($result as $value) {
                if($value->tty == 'SBD') {
                    for($i=0; $i<5; $i++) {
                        if(isset($value->conceptProperties[$i])) {
                            $arr['rxcui'] = $value->conceptProperties[$i]->rxcui;
                            $arr['name'] = $value->conceptProperties[$i]->name;
                            array_push($drugs, $arr);
                        }
                    }
                }
            }
            
            foreach($drugs as $i => $drug) {
                $url = env('drug_domain').'/REST/rxcui/'.$drug['rxcui'].'/historystatus.json';
                $result = Cache::get('drug-'.$drug['rxcui']);
                if(!$result) {
                    $result = $this->cacheRepository->cache_drug_details($url, $drug['rxcui']);
                }
                
                if(isset($result['ingredientAndStrength'])) {
                    $arr = array();
                    foreach($result['ingredientAndStrength'] as $value) {
                        array_push($arr, $value->baseName);
                    }
                    $drugs[$i]['ingredient_base_names'] = $arr;
                }

                if(isset($result['doseFormGroupConcept'])) {
                    $arr = array();
                    foreach($result['doseFormGroupConcept'] as $value) {
                        array_push($arr, $value->doseFormGroupName);
                    }
                    $drugs[$i]['dosage'] = $arr;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $drugs
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'data' => 'No Result Found'
            ], 200);
        }
    }

    function addUserDrug(Request $request) {
        $validate = Validator::make($request->all(), [
            'rxcui' => 'required'
        ]);

        if($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->messages()
            ], 422);
        }

        $rxcui = $request->input('rxcui');
        $url = env('drug_domain')."/REST/rxcui/{$rxcui}/historystatus.json";
        $result = Cache::get("drug-status-{$rxcui}");
        if(!$result) {
            $result = $this->cacheRepository->check_drug_status($url, $rxcui);
        }
        
        if($result == 'Active') {
            $check = UserMedicine::where([
                ['user_id', Auth::guard('api')->user()->id],
                ['rxcui', $rxcui]
            ])->first();

            if(!isset($check->id)) {
                UserMedicine::create([
                    'user_id' => Auth::guard('api')->user()->id,
                    'rxcui' => $rxcui
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Drug Added'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Drug Exists in your list'
                ], 200);
            }
        } else {
            return response()->json([
                'success' => false,
                'errors' => array(
                    'rxcui' => array(
                        'Invalid Drug ID'
                    )
                )
            ], 422);
        }
    }

    function deleteUserDrug(Request $request, $rxcui) {
        $url = env('drug_domain')."/REST/rxcui/{$rxcui}/historystatus.json";
        $result = Cache::get("drug-status-{$rxcui}");
        if(!$result) {
            $result = $this->cacheRepository->check_drug_status($url, $rxcui);
        }
        
        if($result == 'Active') {
            $check = UserMedicine::where([
                ['user_id', Auth::guard('api')->user()->id],
                ['rxcui', $rxcui]
            ])->first();

            if(isset($check->id)) {
                UserMedicine::where('id', $check->id)->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Drug Deleted'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Drug doesn\'t exists in your list'
                ], 200);
            }
        } else {
            return response()->json([
                'success' => false,
                'errors' => array(
                    'rxcui' => array(
                        'Invalid Drug ID'
                    )
                )
            ], 422);
        }
    }

    function getUserDrugs(Request $request) {
        $user_drugs = UserMedicine::where('user_id', Auth::guard('api')->user()->id)->get();
        $data = array();

        foreach($user_drugs as $drug) {
            $url = env('drug_domain')."/REST/rxcui/{$drug->rxcui}/historystatus.json";
            $result = Cache::get("drug-attributes-{$drug->rxcui}");
            if(!$result) {
                $result = $this->cacheRepository->check_drug_attributes($url, $drug->rxcui);
            }

            if($result['status'] == 'Active') {
                $drug_details['rxcui'] = $drug->rxcui;
                $drug_details['name'] = $result['name'];
                if(isset($result['ingredientAndStrength'])) {
                    $arr = array();
                    foreach($result['ingredientAndStrength'] as $value) {
                        array_push($arr, $value->baseName);
                    }
                    $drug_details['ingredient_base_names'] = $arr;
                }
    
                if(isset($result['doseFormGroupConcept'])) {
                    $arr = array();
                    foreach($result['doseFormGroupConcept'] as $value) {
                        array_push($arr, $value->doseFormGroupName);
                    }
                    $drug_details['dosage'] = $arr;
                }

                array_push($data, $drug_details);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }
}

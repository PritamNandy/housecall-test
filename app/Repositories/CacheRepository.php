<?php

namespace App\Repositories;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CacheRepository
{
    function cache_drugs($url, $name) {
        return Cache::remember('drugs-'.$name, 120, function () use($url) {
            $response = Http::get($url);
            $result = json_decode($response->body());
            if(isset($result->drugGroup->conceptGroup)) {
                return $result->drugGroup->conceptGroup;
            }
        });
    }

    function cache_drug_details($url, $rxcui) {
        return Cache::remember('drug-'.$rxcui, 120, function () use($url) {
            $response = Http::get($url);
            $result = json_decode($response->body());
            $data = array();
            if(isset($result->rxcuiStatusHistory->definitionalFeatures->ingredientAndStrength)) {
                $data['ingredientAndStrength'] = $result->rxcuiStatusHistory->definitionalFeatures->ingredientAndStrength;
            }

            if(isset($result->rxcuiStatusHistory->definitionalFeatures->doseFormGroupConcept)) {
                $data['doseFormGroupConcept'] = $result->rxcuiStatusHistory->definitionalFeatures->doseFormGroupConcept;
            }

            return $data;
        });
    }

    function check_drug_status($url, $rxcui) {
        return Cache::remember('drug-status-'.$rxcui, 120, function () use($url) {
            $response = Http::get($url);
            $result = json_decode($response->body());
            if(isset($result->rxcuiStatusHistory->metaData->status)) {
                return $result->rxcuiStatusHistory->metaData->status;
            }
        });
    }

    function check_drug_attributes($url, $rxcui) {
        return Cache::remember('drug-attributes-'.$rxcui, 120, function () use($url) {
            $response = Http::get($url);
            $result = json_decode($response->body());
            $data = array();
            if(isset($result->rxcuiStatusHistory->metaData->status)) {
                $data['status'] = $result->rxcuiStatusHistory->metaData->status;
            }

            if(isset($result->rxcuiStatusHistory->attributes->name)) {
                $data['name'] = $result->rxcuiStatusHistory->attributes->name;
            }

            if(isset($result->rxcuiStatusHistory->definitionalFeatures->ingredientAndStrength)) {
                $data['ingredientAndStrength'] = $result->rxcuiStatusHistory->definitionalFeatures->ingredientAndStrength;
            }

            if(isset($result->rxcuiStatusHistory->definitionalFeatures->doseFormGroupConcept)) {
                $data['doseFormGroupConcept'] = $result->rxcuiStatusHistory->definitionalFeatures->doseFormGroupConcept;
            }

            return $data;
        });
    }
}
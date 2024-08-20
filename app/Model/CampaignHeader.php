<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Acelle\Library\Traits\HasUid;

class CampaignHeader extends Model
{
    use HasFactory;
    use HasUid;

    public function campaign()
    {
        return $this->belongsTo('Acelle\Model\Campaign');
    }

    public static function newDefault()
    {
        $campaignHeader = new self();
        $campaignHeader->editable = true;

        return $campaignHeader;
    }

    public function saveFromRequest($request)
    {
        // fill
        $this->name = $request->name;
        $this->value = $request->value;

        // validate
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'value' => 'required',
        ]);

        // redirect if fails
        if ($validator->fails()) {
            return $validator;
        }

        $this->save();

        return $validator;
    }
}

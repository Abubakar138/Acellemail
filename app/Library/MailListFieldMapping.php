<?php

namespace Acelle\Library;

use Exception;
use Acelle\Model\MailList;
use Acelle\Model\Field;
use DB;

class MailListFieldMapping
{
    public $mapping = [];
    public $list;

    // Use lower-case here
    public $preservedFields = ['tags', 'status'];

    private function __construct($mapping, $list)
    {
        $this->mapping = $mapping;
        $this->list = $list;
    }

    public static function parse(array $mapping, MailList $list)
    {
        self::validate($mapping, $list);

        $mapObj = new self($mapping, $list);

        return $mapObj;
    }

    public function getHeaders()
    {
        return array_keys($this->mapping);
    }

    public static function validate($map, $list)
    {
        // Check if EMAIL (required) is included in the map
        $fieldIds = array_values($map);
        $emailFieldId = $list->getEmailField()->id;

        if (!in_array($emailFieldId, $fieldIds)) {
            throw new Exception(trans('messages.list.import.errors.email_missing'));
        }

        // Check if field id is valid
        foreach ($map as $header => $fieldId) {
            if (!$list->fields()->where('id', $fieldId)->exists()) {
                throw new Exception(trans('messages.list.import.errors.field_id_invalid', ['id' => $fieldId, 'header' => $header, 'list' => $list->name]));
            }
        }
    }


    // Wath a mapping like this:  [ 'First Name' => 1,     'Email'     => 2               , 'tags' => 'SOME TAGS', 'others' => 'Others' ]
    // Transform a record like:   [ 'First Name' => 'Joe', 'Email'     => 'joe@america.us', 'tags' => 'SOME TAGS', 'others' => 'Others' ]
    // To something like:         [ 'field_100'  => 'Joe', 'field_102' => 'joe@america.us', 'tags' => 'SOME TAGS']
    //
    // i.e. Change header based on mapped field, remove other fields (not in map)
    public function processRecord($r, $closure = null)
    {
        // IMPORTANT: 'tags' must be lower-case
        // Extract the relevant fields, including preserved fields
        $selectedFields = array_merge($this->getHeaders(), $this->preservedFields);
        $record = array_only($r, $selectedFields);

        // Change original header to mapped field name
        foreach ($this->mapping as $header => $fieldId) {
            $field = Field::find($fieldId);
            $fieldName = $field->custom_field_name;
            $value = $record[$header];

            // Allow the calling party to do any further processing if needed
            if ($closure) {
                $value = $closure($field, $value);
            }

            $record[$fieldName] = $value;
            unset($record[$header]);
        }

        return $record;
    }

    public function getCustomFields()
    {
        $customFields = array_map(function ($fieldId) {
            $field = Field::find($fieldId);
            $fieldName = $field->custom_field_name;
            return $fieldName;
        }, $this->mapping);

        return $customFields;
    }

    public function createTmpTableFromMapping()
    {
        // create a temporary table containing the input subscribers
        $tmpTable = table('__tmp_subscribers');
        $emailField = $this->list->getEmailField();

        // @todo: hard-coded charset and COLLATE
        $tmpFields = array_map(function ($fieldName) use ($emailField) {
            if ($emailField->custom_field_name == $fieldName) {
                $dataType = 'VARCHAR(255)'; // VARCHAR type is required if we add an index on this field
            } else {
                $dataType = 'MEDIUMTEXT';
            }
            return "`{$fieldName}` {$dataType} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        }, $this->getCustomFields());

        // Also create columsn for preserved fields like 'tags'
        foreach($this->preservedFields as $fieldName) {
            $tmpFields[] = "`{$fieldName}` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        }

        $tmpFields = implode(',', $tmpFields);

        // Drop table, create table and create index
        DB::statement("DROP TABLE IF EXISTS {$tmpTable};");
        DB::statement("CREATE TABLE {$tmpTable}({$tmpFields}) ENGINE=InnoDB;");
        DB::statement("CREATE INDEX _index_email_{$tmpTable} ON {$tmpTable}(`{$emailField->custom_field_name}`);");

        return [$tmpTable, $emailField->custom_field_name];
    }
}

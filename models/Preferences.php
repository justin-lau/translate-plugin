<?php namespace RainLab\Translate\Models;

use ApplicationException;
use Config;
use DB;
use Lang;
use Model;
use Schema;

/**
 * Language Plugin Preferences
 *
 * @package rainlab\translate
 * @author Justin Lau
 */
class Preferences extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'rainlab_translate_preferences';

    public $settingsFields = 'fields.yaml';

    /**
     * Default values to set for this model
     */
    public function initSettingsData()
    {
        $this->enable_fulltext_search = false;
    }

    /**
     * Mutator of the `enable_fulltext_search` setting. Throws ApplicationException if
     * the database is not compatible.
     *
     * TODO: Move these logic to appropriate controller.
     *
     * @param $value
     * @throws ApplicationException
     */
    public function setEnableFulltextSearchAttribute($value)
    {
        if ($value == $this->attributes['enable_fulltext_search'])
            return;

        if ($value == true)
            self::addFulltextIndex();
        else
            self::dropFulltextIndex();

        $this->attributes['enable_fulltext_search'] = $value;
    }

    /**
     * Check for database compatibility.
     * @throws ApplicationException
     */
    protected static function validateDatabase()
    {
        // MySQL 5.6.0+ only
        if ('mysql' != ($driver_name = DB::getDriverName()))
            throw new ApplicationException(Lang::get('rainlab.translate::lang.preferences.enable_fulltext_search.mysql_only', compact('driver_name')));

        $mysql_version = DB::select('SHOW VARIABLES WHERE `Variable_name` = "version"')[0]->Value;

        if (version_compare($mysql_version, '5.6.0', '<'))
            throw new ApplicationException(Lang::get('rainlab.translate::lang.preferences.enable_fulltext_search.mysql_560_plus', compact('mysql_version')));
    }

    /**
     * Add fulltext index to the `rainlab_translate_attributes` table for the `attribute_data` column
     */
    protected static function addFulltextIndex()
    {
        self::validateDatabase();

        $table_name  = 'rainlab_translate_attributes';
        $column_name = 'attribute_data';
        $index_name  = $table_name . '_' . Config::get('rainlab.translate::fulltext_index_suffix', 'fulltext');
        $statement   = 'ALTER TABLE `' . $table_name . '` ADD FULLTEXT INDEX `' . $index_name . '` (`' . $column_name . '`)';

        DB::statement($statement);
    }

    /**
     * Drop fulltext index
     */
    protected static function dropFulltextIndex()
    {
        self::validateDatabase();

        $table_name  = 'rainlab_translate_attributes';
        $index_name  = $table_name . '_' . Config::get('rainlab.translate::fulltext_index_suffix', 'fulltext');

        Schema::table($table_name, function ($table) use ($index_name) {
            $table->dropIndex($index_name);
        });
    }

}

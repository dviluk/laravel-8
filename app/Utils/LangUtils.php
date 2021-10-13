<?php

namespace App\Utils;

class LangUtils
{
    /**
     * Retorna el idioma actual de la aplicación.
     * 
     * Ej: `en` | 'es'
     *
     * @param boolean $fromRequest Indica si el idioma esta especificado en la petición
     * @return string
     */
    public function getCurrent($fromRequest = false)
    {
        if ($fromRequest) {
            return request()->app_lang ?? 'en';
        }

        return 'en';
    }

    /**
     * Retorna todos los lenguajes reportados por la aplicación.
     * 
     * @return array 
     */
    public function all()
    {
        return ['en'];
    }

    /**
     * Retorna todos los lenguajes reportados por la aplicación 
     * utilizándolo como Key en el arreglo.
     * 
     * @return array 
     */
    public function allByKey()
    {
        return array_flip($this->all());
    }

    public function primaryLang()
    {
        return 'en';
    }

    /**
     * Se encarga de generar las reglas para un input localizado.
     * 
     * @param mixed $inputName 
     * @param mixed $rules 
     * @return array 
     */
    public function inputRulesLocalized($inputName, $rules, ?array $langs = null)
    {
        $inputRules = [];

        $currentLangs = $langs ?? $this->all();

        foreach ($currentLangs as $lang) {
            $inputLocalized = "{$inputName}_{$lang}";
            if ($rules instanceof \Closure) {
                $inputRules[$inputLocalized] = $rules($inputLocalized);
            } else {
                $inputRules[$inputLocalized] = $rules;
            }
        }

        return $inputRules;
    }

    /**
     * Genera un arreglo con los nombre del input localizados.
     * 
     * name => [name_es, name_en]
     * 
     * @param mixed $inputName 
     * @return array 
     */
    public function inputNameLocalized($inputName, ?array $langs = null)
    {
        $inputs = [];

        $currentLangs = $langs ?? $this->all();

        foreach ($currentLangs as $lang) {
            $inputs[] = "{$inputName}_{$lang}";
        }

        return $inputs;
    }

    /**
     * Helper para combinar los inputs a localizar y los otros inputs.
     * 
     * `$request->only(combineInputNamesLocalized)`
     * 
     * @param array $inputsToLocalize 
     * @param array $otherInputs 
     * @return array 
     */
    public function combineLocalizedInputNames(array $inputsToLocalize, array $otherInputs = [], ?array $langs = null)
    {
        $inputs = [];

        $currentLangs = $langs ?? $this->all();

        foreach ($inputsToLocalize as $input) {
            $inputs = array_merge($inputs, $this->inputNameLocalized($input, $currentLangs));
        }

        $inputs = array_merge($inputs, $otherInputs);

        return $inputs;
    }

    /**
     * Helper para combinar las reglas de los inputs a localizar y las otras reglas.
     * 
     * `Validator::validate($request, combineInputRulesLocalized(inputsRulesLocalized, otherInputRules))`
     * 
     * @param array $inputsToLocalize 
     * @param array $otherInputsRules 
     * @return array 
     */
    public function combineLocalizedInputRules(array $inputsToLocalize, array $otherInputsRules = [], ?array $langs = null)
    {
        $inputRules = [];

        $currentLangs = $langs ?? $this->all();

        foreach ($inputsToLocalize as $input => $rules) {
            $inputRules = array_merge($inputRules, $this->inputRulesLocalized($input, $rules, $currentLangs));
        }

        $inputRules = array_merge($inputRules, $otherInputsRules);

        return $inputRules;
    }

    /**
     * Extrae las traducciones de los campos especificados en `$keys`.
     * 
     * Retorna un arreglo con los siguientes indices:
     * 
     * ```
     * [
     *   // Traducciones del registro
     *  'translations' => array,
     *   // La traducción para el idioma por default de la aplicación
     *  'default_translation' => array,
     *   // La traduccion para el idioma actual de la aplicación
     *  'current_translation' => array,
     *   // Los campos sin las traducciones, se deben agregar manualmente después de la extracción
     *  'data' => array,
     * ]
     * ```
     * 
     * @param mixed $data 
     * @param array $keys 
     * @param array $extraData 
     * @return array 
     */
    public function extractTranslations($data, array $keys = [], array $extraData = [])
    {
        $langs = $this->all();
        $currentLang = $this->getCurrent();
        $primaryLang = $this->primaryLang();

        $keyValues = [];

        $defaultTranslations = null;
        $currentTranslations = null;

        foreach ($keys as $key => $columnName) {
            foreach ($langs as $lang) {
                $inputKey = "{$key}_{$lang}";

                if (isset($data[$inputKey])) {
                    if ($lang === $primaryLang) {
                        $defaultTranslations[$key] = $data[$inputKey];
                    }

                    if ($lang === $currentLang) {
                        $currentTranslations[$key] = $data[$inputKey];
                    }

                    $keyValues[$lang] = array_merge([
                        $columnName => $data[$inputKey],
                        'lang_id' => $lang,
                    ], $extraData, $keyValues[$lang] ?? []);

                    unset($data[$inputKey]);
                }
            }
        }

        // TODO: defaultTranslations fallback
        // TODO: currentTranslations fallback

        return [
            'translations' => $keyValues,
            'default_translation' => $defaultTranslations,
            'current_translation' => $currentTranslations,
            'data' => $data,
        ];
    }

    /**
     * Retorna el nombre de la columna con el sufijo del idioma actual
     * de la aplicación
     *
     * @param string $columnNameWithoutLang
     * @return string
     */
    public function dbColumn($columnNameWithoutLang)
    {
        $currentLang = $this->getCurrent();
        return "{$columnNameWithoutLang}_{$currentLang}";
    }

    /**
     * Se encarga de localizar un key dentro del arreglo.
     * 
     * Se utiliza el helper `__('dictionary.key')`.
     * 
     * @param mixed $collection 
     * @param mixed $key Nombre del campo a localizar
     * @param mixed $keyInDictionary Grupo en los diccionarios `ejemplo.dic`
     * @return array 
     * @throws \Illuminate\Contracts\Container\BindingResolutionException 
     */
    public function localizeKeyInArray($collection, $key, $keyInDictionary)
    {
        $localized = [];

        foreach ($collection as $item) {
            $originalValue = $item[$key];
            $valueLocalized = __($keyInDictionary . '.' . $originalValue);

            $item[$key] = $valueLocalized;

            $localized[] = $item;
        }

        return $localized;
    }
}

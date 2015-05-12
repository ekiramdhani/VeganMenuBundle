<?php
/**
 * Autor: Lukáš Brzák
 * lukas.brzak@email.cz
 * komponenta pro převedení textu na user-friendly url
 */

namespace Vegan\Component;

/**
 * Component that can generate friendly-url based string
 * For example:
 *      SlugGenerator::generateSlug('? What ~\!# the &&& hell --- is ... it?????') will return string 'what-the-hell-is-it'
 *
 */
class SlugGenerator
{
    /**
     * Method for generating `slug` (= friendly URL key like 'hello-world')
     *
     * @param string|array| $str
     * @param string $locale
     * @param array $remove
     * @param string $delimiter
     *
     * @return string unique key used for
     */
    public static function generate($str, array $remove = array(), $delimiter = '-', $locale = 'cs_CZ.UTF8') {
        $str = self::translit($str);

        $oldLocale = setlocale  (LC_ALL,"0");
        setlocale(LC_ALL, $locale);

        if( count($remove) > 0 ) {
            $str = str_replace($remove, ' ', $str);
        }


        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", $delimiter, $clean);
        $clean = preg_replace("/\\{$delimiter}+/", $delimiter, $clean);
        $clean = strtolower(trim($clean, $delimiter));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        $clean = trim($clean);

        setlocale(LC_ALL, $oldLocale);
        return trim($clean,"-");
    }


    /**
     * Method for string transliteration
     *
     * @param string|array|Object $string
     * @param null $fromCharset
     * @param null $fromLocale
     *
     * @return null|string
     */
    public static function translit (&$string, $fromCharset = null, $fromLocale = null) {
        if (!is_string($string)) {
            if (is_array($string)) {
                $string = implode(' ', $string);
            } else if (is_object($string)) {
                if (method_exists($string, '__toString')) {
                    $string = (string)$string;
                } else {
                    throw new \InvalidArgumentException("It's impossible to convert object `" . get_class($string) . "` to string!");
                }
            } else {
                try {
                    $string = (string)$string;
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException("Argument 1 passed to method `translit()`: \$string is impossible to convert to string! " . $e->getMessage());
                }
            }

        }
        if (false === $string) {
            return null;
        }
        if ($locale = setlocale(LC_CTYPE, null)) {
            preg_match('~^([a-z]{2})(?:[-_]([a-z]{2}))?(\..+)?$~i', $locale, $tmp);
        }
        if ($fromLocale === null) {
            $fromLocale = empty($tmp[1]) ? 'en' : (strtolower($tmp[1]) . (empty($tmp[2]) ? '' : '-' . strtolower($tmp[2])));
        }
        if (!$fromCharset) {
            $fromCharset = empty($tmp[3]) ? 'utf-8' : $tmp[3];
        }
        if ($fromCharset == 'utf-8' || preg_match('~^utf-?8$~i', $fromCharset)) {
            $stringReference = &$string;
        } else if (!$stringReference = mb_convert_encoding($string, 'utf-8', $fromCharset)) {
            return false;
        }

        $locale = &$fromLocale;

        $specialRegularExpression = null;

        if (!strncmp($locale, 'en', 2)) {
            self::$translitableList = array_merge(self::$translitableList, array(
                'Ä'=>'A',
                'ä'=>'a',
                'Ö'=>'O',
                'ö'=>'o',
                'Ü'=>'U',
                'ü'=>'u',
            ));
        } else if ($locale == 'fi-fi') {
            self::$translitableList = array_merge(self::$translitableList, array(
                'ä'=>'a',
                'ö'=>'o',
                'ü'=>'u',
                'Ä'=>'A',
                'Ö'=>'O',
            ));
        } else if (!strncmp($locale,'fr',2)) {
            self::$translitableList = array_merge(self::$translitableList, array(
                'Æ'=>'Ae',
                'Ä'=>'A',
                'ä'=>'a',
                'Ö'=>'O',
                'ö'=>'o',
                'Ü'=>'U',
                'ü'=>'u',
                "'"=>'-',
                '’'=>'-',
            ));
        } else if ($locale == 'is-is') {
            self::$translitableList = array_merge(self::$translitableList, array(
                'Æ'=>'Ae',
            ));
        } else if (!strncmp($locale,'ua',2)) {
            self::$translitableList = array_merge(self::$translitableList, array(
                'и'=>'y',
                'ѣ'=>'i',
            ));
        } else if (!strncmp($locale,'ru',2)) {

            $specialRegularExpression[] = array('~(?<![бвгджзклмнпрстфхцчшщБВГДЖЗКЛМНПРСТФХЦЧШЩ])[её]~us', 'ye');
            $specialRegularExpression[] = array('~(?<![БВГДЖЗКЛМНПРСТФХЦЧШЩ])[ЕЁ](?![а-яёy])~us', 'YE');
            $specialRegularExpression[] = array('~(?<![БВГДЖЗКЛМНПРСТФХЦЧШЩ])[ЕЁ]~us', 'Ye');
            $specialRegularExpression[] = array('~([ЖХЦЧШЩЮЯ])(?![а-яёy])~use', 'mb_convert_case(self::$translitableList["$1"],MB_CASE_UPPER,"utf-8")');
            self::$translitableList = array_merge(self::$translitableList, array(
                'Ї'=>'I',
                'ї'=>'i',
            ));
        }

        if (!empty($specialRegularExpression)) {
            $tmpReference = &$stringReference;
            foreach ($specialRegularExpression as $regular) {
                $tmpReference = preg_replace($regular[0], $regular[1],$tmpReference);
            }
            $stringReference = &$tmpReference;
        }

        $result = null;
        $len = mb_strlen($stringReference,'utf-8');

        for ($i = 0; $i < $len; $i++)
        {
            $c = mb_substr($stringReference, $i, 1, 'utf-8');
            $result .= isset( self::$translitableList[$c] ) ? self::$translitableList[$c] : (ord($c) < 0x80 ? $c : '?');
        }
        return $result;
    }


    /**
     * @var array
     */
    private static $translitableList = array(
        'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'Ae','Å'=>'A','Æ'=>'A','Ā'=>'A','Ą'=>'A','Ă'=>'A','Ç'=>'C','Ć'=>'C','Č'=>'C','Ĉ'=>'C','Ċ'=>'C','Ď'=>'D','Đ'=>'D','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E','Ē'=>'E','Ę'=>'E','Ě'=>'E','Ĕ'=>'E','Ė'=>'E','Ĝ'=>'G','Ğ'=>'G','Ġ'=>'G','Ģ'=>'G','Ĥ'=>'H','Ħ'=>'H','Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ī'=>'I','Ĩ'=>'I','Ĭ'=>'I','Į'=>'I','İ'=>'I','Ĳ'=>'IJ','Ĵ'=>'J','Ķ'=>'K','Ľ'=>'K','Ĺ'=>'K','Ļ'=>'K','Ŀ'=>'K','Ł'=>'L','Ñ'=>'N','Ń'=>'N','Ň'=>'N','Ņ'=>'N','Ŋ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'Oe','Ø'=>'O','Ō'=>'O','Ő'=>'O','Ŏ'=>'O','Œ'=>'OE','Ŕ'=>'R','Ř'=>'R','Ŗ'=>'R','Ś'=>'S','Ş'=>'S','Ŝ'=>'S','Ș'=>'S','Š'=>'S','Ť'=>'T','Ţ'=>'T','Ŧ'=>'T','Ț'=>'T','Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'Ue','Ū'=>'U','Ů'=>'U','Ű'=>'U','Ŭ'=>'U','Ũ'=>'U','Ų'=>'U','Ŵ'=>'W','Ŷ'=>'Y','Ÿ'=>'Y','Ý'=>'Y','Ź'=>'Z','Ż'=>'Z','Ž'=>'Z','à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'ae','ā'=>'a','ą'=>'a','ă'=>'a','å'=>'a','æ'=>'ae','ç'=>'c','ć'=>'c','č'=>'c','ĉ'=>'c','ċ'=>'c','ď'=>'d','đ'=>'d','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ē'=>'e','ę'=>'e','ě'=>'e','ĕ'=>'e','ė'=>'e','ƒ'=>'f','ĝ'=>'g','ğ'=>'g','ġ'=>'g','ģ'=>'g','ĥ'=>'h','ħ'=>'h','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ī'=>'i','ĩ'=>'i','ĭ'=>'i','į'=>'i','ı'=>'i','ĳ'=>'ij','ĵ'=>'j','ķ'=>'k','ĸ'=>'k','ł'=>'l','ľ'=>'l','ĺ'=>'l','ļ'=>'l','ŀ'=>'l','ñ'=>'n','ń'=>'n','ň'=>'n','ņ'=>'n','ŉ'=>'n','ŋ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'oe','ø'=>'o','ō'=>'o','ő'=>'o','ŏ'=>'o','œ'=>'oe','ŕ'=>'r','ř'=>'r','ŗ'=>'r','ś'=>'s','š'=>'s','ť'=>'t','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'ue','ū'=>'u','ů'=>'u','ű'=>'u','ŭ'=>'u','ũ'=>'u','ų'=>'u','ŵ'=>'w','ÿ'=>'y','ý'=>'y','ŷ'=>'y','ż'=>'z','ź'=>'z','ž'=>'z','ß'=>'ss','ſ'=>'ss','Α'=>'A','Ά'=>'A','Ἀ'=>'A','Ἁ'=>'A','Ἂ'=>'A','Ἃ'=>'A','Ἄ'=>'A','Ἅ'=>'A','Ἆ'=>'A','Ἇ'=>'A','ᾈ'=>'A','ᾉ'=>'A','ᾊ'=>'A','ᾋ'=>'A','ᾌ'=>'A','ᾍ'=>'A','ᾎ'=>'A','ᾏ'=>'A','Ᾰ'=>'A','Ᾱ'=>'A','Ὰ'=>'A','Ά'=>'A','ᾼ'=>'A','Β'=>'B','Γ'=>'G','Δ'=>'D','Ε'=>'E','Έ'=>'E','Ἐ'=>'E','Ἑ'=>'E','Ἒ'=>'E','Ἓ'=>'E','Ἔ'=>'E','Ἕ'=>'E','Έ'=>'E','Ὲ'=>'E','Ζ'=>'Z','Η'=>'I','Ή'=>'I','Ἠ'=>'I','Ἡ'=>'I','Ἢ'=>'I','Ἣ'=>'I','Ἤ'=>'I','Ἥ'=>'I','Ἦ'=>'I','Ἧ'=>'I','ᾘ'=>'I','ᾙ'=>'I','ᾚ'=>'I','ᾛ'=>'I','ᾜ'=>'I','ᾝ'=>'I','ᾞ'=>'I','ᾟ'=>'I','Ὴ'=>'I','Ή'=>'I','ῌ'=>'I','Θ'=>'TH','Ι'=>'I','Ί'=>'I','Ϊ'=>'I','Ἰ'=>'I','Ἱ'=>'I','Ἲ'=>'I','Ἳ'=>'I','Ἴ'=>'I','Ἵ'=>'I','Ἶ'=>'I','Ἷ'=>'I','Ῐ'=>'I','Ῑ'=>'I','Ὶ'=>'I','Ί'=>'I','Κ'=>'K','Λ'=>'L','Μ'=>'M','Ν'=>'N','Ξ'=>'KS','Ο'=>'O','Ό'=>'O','Ὀ'=>'O','Ὁ'=>'O','Ὂ'=>'O','Ὃ'=>'O','Ὄ'=>'O','Ὅ'=>'O','Ὸ'=>'O','Ό'=>'O','Π'=>'P','Ρ'=>'R','Ῥ'=>'R','Σ'=>'S','Τ'=>'T','Υ'=>'Y','Ύ'=>'Y','Ϋ'=>'Y','Ὑ'=>'Y','Ὓ'=>'Y','Ὕ'=>'Y','Ὗ'=>'Y','Ῠ'=>'Y','Ῡ'=>'Y','Ὺ'=>'Y','Ύ'=>'Y','Φ'=>'F','Χ'=>'X','Ψ'=>'PS','Ω'=>'O','Ώ'=>'O','Ὠ'=>'O','Ὡ'=>'O','Ὢ'=>'O','Ὣ'=>'O','Ὤ'=>'O','Ὥ'=>'O','Ὦ'=>'O','Ὧ'=>'O','ᾨ'=>'O','ᾩ'=>'O','ᾪ'=>'O','ᾫ'=>'O','ᾬ'=>'O','ᾭ'=>'O','ᾮ'=>'O','ᾯ'=>'O','Ὼ'=>'O','Ώ'=>'O','ῼ'=>'O','α'=>'a','ά'=>'a','ἀ'=>'a','ἁ'=>'a','ἂ'=>'a','ἃ'=>'a','ἄ'=>'a','ἅ'=>'a','ἆ'=>'a','ἇ'=>'a','ᾀ'=>'a','ᾁ'=>'a','ᾂ'=>'a','ᾃ'=>'a','ᾄ'=>'a','ᾅ'=>'a','ᾆ'=>'a','ᾇ'=>'a','ὰ'=>'a','ά'=>'a','ᾰ'=>'a','ᾱ'=>'a','ᾲ'=>'a','ᾳ'=>'a','ᾴ'=>'a','ᾶ'=>'a','ᾷ'=>'a','β'=>'b','γ'=>'g','δ'=>'d','ε'=>'e','έ'=>'e','ἐ'=>'e','ἑ'=>'e','ἒ'=>'e','ἓ'=>'e','ἔ'=>'e','ἕ'=>'e','ὲ'=>'e','έ'=>'e','ζ'=>'z','η'=>'i','ή'=>'i','ἠ'=>'i','ἡ'=>'i','ἢ'=>'i','ἣ'=>'i','ἤ'=>'i','ἥ'=>'i','ἦ'=>'i','ἧ'=>'i','ᾐ'=>'i','ᾑ'=>'i','ᾒ'=>'i','ᾓ'=>'i','ᾔ'=>'i','ᾕ'=>'i','ᾖ'=>'i','ᾗ'=>'i','ὴ'=>'i','ή'=>'i','ῂ'=>'i','ῃ'=>'i','ῄ'=>'i','ῆ'=>'i','ῇ'=>'i','θ'=>'th','ι'=>'i','ί'=>'i','ϊ'=>'i','ΐ'=>'i','ἰ'=>'i','ἱ'=>'i','ἲ'=>'i','ἳ'=>'i','ἴ'=>'i','ἵ'=>'i','ἶ'=>'i','ἷ'=>'i','ὶ'=>'i','ί'=>'i','ῐ'=>'i','ῑ'=>'i','ῒ'=>'i','ΐ'=>'i','ῖ'=>'i','ῗ'=>'i','κ'=>'k','λ'=>'l','μ'=>'m','ν'=>'n','ξ'=>'ks','ο'=>'o','ό'=>'o','ὀ'=>'o','ὁ'=>'o','ὂ'=>'o','ὃ'=>'o','ὄ'=>'o','ὅ'=>'o','ὸ'=>'o','ό'=>'o','π'=>'p','ρ'=>'r','ῤ'=>'r','ῥ'=>'r','σ'=>'s','ς'=>'s','τ'=>'t','υ'=>'y','ύ'=>'y','ϋ'=>'y','ΰ'=>'y','ὐ'=>'y','ὑ'=>'y','ὒ'=>'y','ὓ'=>'y','ὔ'=>'y','ὕ'=>'y','ὖ'=>'y','ὗ'=>'y','ὺ'=>'y','ύ'=>'y','ῠ'=>'y','ῡ'=>'y','ῢ'=>'y','ΰ'=>'y','ῦ'=>'y','ῧ'=>'y','φ'=>'f','χ'=>'x','ψ'=>'ps','ω'=>'o','ώ'=>'o','ὠ'=>'o','ὡ'=>'o','ὢ'=>'o','ὣ'=>'o','ὤ'=>'o','ὥ'=>'o','ὦ'=>'o','ὧ'=>'o','ᾠ'=>'o','ᾡ'=>'o','ᾢ'=>'o','ᾣ'=>'o','ᾤ'=>'o','ᾥ'=>'o','ᾦ'=>'o','ᾧ'=>'o','ὼ'=>'o','ώ'=>'o','ῲ'=>'o','ῳ'=>'o','ῴ'=>'o','ῶ'=>'o','ῷ'=>'o','¨'=>'','΅'=>'','᾿'=>'','῾'=>'','῍'=>'','῝'=>'','῎'=>'','῞'=>'','῏'=>'','῟'=>'','῀'=>'','῁'=>'','΄'=>'','΅'=>'','`'=>'','῭'=>'','ͺ'=>'','᾽'=>'','А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'E','Ж'=>'Zh','З'=>'Z','И'=>'I','Й'=>'Y','К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'Kh','Ц'=>'Ts','Ч'=>'Ch','Ш'=>'Sh','Щ'=>'Shch','Ы'=>'Y','Э'=>'E','Ю'=>'Yu','Я'=>'Ya','а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','Ъ'=>'','ъ'=>'','Ь'=>'','ь'=>'','ѣ'=>'e','ð'=>'d','Ð'=>'D','þ'=>'th','Þ'=>'Th','Ї'=>'Yi','ї'=>'yi','І'=>'I','і'=>'i','Ѓ'=>'Gj','ѓ'=>'gj','Є'=>'Ie','є'=>'ie','ა'=>'a','ბ'=>'b','გ'=>'g','დ'=>'d','ე'=>'e','ვ'=>'v','ზ'=>'z','თ'=>'t','ი'=>'i','კ'=>'k','ლ'=>'l','მ'=>'m','ნ'=>'n','ო'=>'o','პ'=>'p','ჟ'=>'zh','რ'=>'r','ს'=>'s','ტ'=>'t','უ'=>'u','ფ'=>'p','ქ'=>'k','ღ'=>'gh','ყ'=>'q','შ'=>'sh','ჩ'=>'ch','ც'=>'ts','ძ'=>'dz','წ'=>'ts','ჭ'=>'ch','ხ'=>'kh','ჯ'=>'j','ჰ'=>'h',''=>'','‒'=>'-','–'=>'-','—'=>'-','―'=>'-','«'=>'"','»'=>'"','“'=>'"','”'=>'"','„'=>'"','”'=>'"','‘'=>"'",'’'=>"'",'´'=>'`','′'=>"'",'″'=>"''",'‴'=>"'''",'…'=>'...','。'=>'.','，'=>',','¦'=>'|','⟨'=>'<','⟩'=>'>','〈'=>'<','〉'=>'>','≪'=>'<<','≫'=>'>>','×'=>'x','÷'=>':','∗'=>'*','∼'=>'~','∽'=>'~','№'=>'#','¼'=>'1/4','½'=>'1/2','¾'=>'3/4','©'=>'(c)','®'=>'(R)','™'=>'(TM)','¹'=>'^1','²'=>'^2','³'=>'^3','⁴'=>'^4','⁵'=>'^5','⁶'=>'^6','⁷'=>'^7','⁸'=>'^8','⁹'=>'^9','ª'=>'^a','€'=>'EUR','﹩'=>'$','¢'=>'cent','£'=>'GBP','¥'=>'JPY','＄'=>'$','￠'=>'cent','￡'=>'GPB','￥'=>'YPY',
    );

}
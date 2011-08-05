<?php
class AlfrescoUtil {
    public static function arrayify(&$maybeArray) {

        if (is_array($maybeArray)) {
            return $maybeArray;
        } else {
            return array($maybeArray);
        }
    }
}   
?>
<?php

/**
 * Two Kings Form Class, to construct web based forms, do validation and
 * handle the output.
 *
 * For more information, read: http://twokings.eu/tools/
 *
 * Two Kings Form Class and all its parts are licensed under the GPL version 2.
 * see: http://www.twokings.eu/tools/license for more information.
 *
 * @version 1.2
 * @author Bob den Otter, bob@twokings.nl, PivotX dev. team
 * @copyright GPL, version 2
 * @link http://twokings.eu/tools/
 *
 * $Rev:: 702                                            $: SVN revision,
 * $Author:: pivotlog                                    $: author and
 * $Date:: 2007-09-22 20:25:00 +0200 (za, 22 sep 2007)   $: date of last commit
 *
 */


define("FORM_NOTPOSTED", 0);
define("FORM_HASERRORS", 1);
define("FORM_OK", 2);

/**
 * Two Kings Form Class
 *
 * For creating and validating (x)html forms.
 *
 */
class Form {

    var $html = array();
    var $fields = array();
    var $hidden_fields = array();
    var $formname = "";
    var $upload_success = false;
    var $lastuploadfile = '';
    var $use_javascript = false;
    var $uploads = array();
    var $submitloc = " bottom";
    var $tabindex = 500;




    function form($name, $action="", $submit="Submit") {

        // Set up the default HTML for the various form elements
        $this->init_html();

        // Determine the protocol to use.
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=="on") {
            $protocol = "https://";
        } else {
            $protocol = "http://";
        }

        // Set the 'action' attribute for the form. (whereto it will submit)
        if ($action=="") {
            $action = $protocol . $_SERVER['HTTP_HOST'] .
                htmlspecialchars($_SERVER['PHP_SELF']) . "?" . $_SERVER['QUERY_STRING'];
        }

        // Always remove the 'retry=1' from the action.
        $action = preg_replace('/&(amp;)?retry=[0-9]+/Ui', "", $action);

        $this->action = $action;
        $this->submit = $submit;
        $this->formname = $name;


    }


    /**
     * Initialise the XHTML snippets to be used for generating the forms.
     *
     */
    function init_html() {

        if (!isMobile()) {
            include(dirname(__FILE__) . "/formclass_defaulthtml.php");
        } else {
            include(dirname(__FILE__) . "/formclass_mobile.php");
        }

    }

    /**
     * Add a field (or header) to the form. See the readme for information about the
     * format of the array to pass.
     *
     * @param array $params
     *
     */
    function add( $params ) {

        // get the default value for this fieldtype
        $default = $this->defaultfield( $params['type'], $params['name'], $params['sessionvariable'] );

        $temp_field = array_merge($default, $params);

        // If there are _POST variables, set them in 'post_value'
        if ( (isset($temp_field['name'])) && (isset($_POST[ $temp_field['name'] ]))) {
            $temp_field['post_value'] = $_POST[ $temp_field['name'] ];
        }

        // Add 'any' to the validation, if the field is required. Can't have required fields that are empty.
        if ($temp_field['isrequired'] == 1) {
            $temp_field['validation'] .= "|any";
        }

        // Set the field, merging the defaults with the parameters..
        $this->fields[] = $temp_field;

        // File uploads
        if ( ($temp_field['type'] == 'file') || ($temp_field['type'] == 'uploadselectbox') ) {

            // Get array keys of $_FILES
            $array_keys   = array_keys($_FILES);
            $count_arrays = count($array_keys);

            for($i=0; $i<$count_arrays; $i++) {


                // To handle the upload correctly, we pass it the right name of the item in $_FILES.
                // Perhaps this is the $temp_field['name'], but we also try it with '_upload' appended.
                if (isset($_FILES[$temp_field['name']])) {
                    $name = $_FILES[$temp_field['name']]['name'];
                } else {
                    $name = $_FILES[$temp_field['name']."_upload"]['name'];
                }

                if ($this->handleUpload($name, $temp_field['upload_dir'], $temp_field['maxfilesize'], $temp_field['allowed_types'], $array_keys[$i])) {
                    $this->upload_success = TRUE;
                }

            }



        }
    }

    /**
     * Remove a field from the form.
     *
     * @param string $name
     */
    function remove( $name ) {

        foreach($this->fields as $key => $field) {
            if ($field['name'] == $name) {
                unset($this->fields[$key]);
            }
        }
    }


    /**
     * Sets the default values for a new field. If we're lazy with regard to
     * setting the form fields, we will assume these sensible defaults.
     *
     * @param string $fieldtype
     *
     */
    function defaultfield( $fieldtype, $name, $sessionvariable="" ) {

        $field = array(
            'error' => "Error in this field..",
            'text' => "",
            'name' => $name,
            'label' => "",
            'value' => "",
            'extra' => "",
            'validation' => "",
            'isrequired' => 0,
            'show_error' => 0,
            'class' => "",
            'style' => ""
        );


        switch ($fieldtype) {

            case 'text':
                $field['name'] = 'text[]';
                $field['size'] = 20;
                break;

            case 'header':
                $field['name'] = 'header';
                break;

            case 'hidden':
                $field['name'] = 'hidden[]';
                break;

            case 'password':
                $field['name'] = 'password[]';
                $field['size'] = 20;
                break;

            case 'checkbox':
                $field['name'] = 'checkbox[]';
                $field['value'] = -1;
                break;

            case 'radio':
                $field['name'] = 'radio[]';
                $field['value'] = -1;
                break;

            case 'select':
            case 'color_select':
            case 'add_select':
                $field['name'] = 'select[]';
                $field['value'] = -1;
                $field['size'] = 0;
                $field['multiple'] = false;
                break;


            case 'textarea':
                $field['name'] = 'textarea[]';
                $field['rows'] = 6;
                $field['cols'] = 60;
                break;

            case 'file':
                $field['name'] = 'file[]';
                break;

            case 'slider':
                $field['name'] = 'slider[]';
                $field['size'] = '200';
                $field['value'] = 5;
                $field['min'] = 0;
                $field['max'] = 10;
                $field['stepsize'] = 1;
                $field['stepsize'] = 1;
                break;

            case 'csrf':
                $field['name'] = 'csrfcheck';
                $field['validation'] = "equal=".$_SESSION[ $sessionvariable ];
                break;

        }

        return $field;

    }

    /**
     * Fetch the form output as HTML, and pass it to the browser.
     *
     * @param boolean $skip_submit
     */
    function display($skip_submit = false) {

        echo $this->fetch($skip_submit);

    }

    /**
     * Fetch the form output as HTML, and return it.
     *
     * @param boolean $skip_submit
     */
    function fetch($skip_submit = false) {

        $this->html['start'] = str_replace('%name%', $this->formname, $this->html['start']);
        $this->html['start'] = str_replace('%action%', $this->action, $this->html['start']);
        $this->html['submit'] = str_replace('%submit%', $this->submit, $this->html['submit']);

        $output = "";

        $output .= $this->html['start']."\n";


        foreach ($this->fields as $field) {
            $output .= $this->renderfield($field);


            // After the header we print a submit button, if we need one.
            if ( ($field['type']=="header") && (!$skip_submit) && (strpos($this->submitloc, "top") > 0) ) {
                $submit = str_replace("%tabindex%", $this->tabindex++, $this->html['submit']);
                $output .= $submit."\n";
            }

        }


        if ( (!$skip_submit) && (strpos($this->submitloc, "bottom") > 0) ) {
            $this->tabindex++;
            $submit = str_replace("%tabindex%", $this->tabindex++, $this->html['submit']);
            $output .= $submit."\n";
        }

        $output .= $this->html['finish']."\n";

        // Insert any hidden fields (collect by the renderfield calls above).
        if (strpos($output,'%hidden_fields%')) {
            $output = str_replace("%hidden_fields%", implode("\n", $this->hidden_fields), $output);
        } else {
            $output = preg_replace('/(<form[^>]+>)/', '$1'."\n".implode("\n", $this->hidden_fields), $output);
        }

        $output .= $this->_fetch_script();

        return $output;

    }

    /**
     * Compile the javascript to use for client-side validation, in addition to
     * the server-side validation.
     *
     * This generates a piece of javascript that uses Jquery and the validation
     * plugin to do the nitty-gritty.
     *
     * Note: Not all fields and not all validation criteria work (yet)
     *
     * @see http://jquery.com
     * @see http://bassistance.de/jquery-plugins/jquery-plugin-validation/
     *
     * @return string
     */
    function _fetch_script() {

        // If we're not using client-side validation, return.
        if ($this->use_javascript == false) {
            return "";
        }




        $rules = array();
        $messages = array();

        // Iterate over all of the fields.
        foreach ($this->fields as $field) {

            // If the field has no validation criteria, continue with the next one.
            if ($field['validation']=="") {
                continue;
            }

            // Use a switch, so we can use different actions for different
            // field types..
            switch ($field['type']) {

                case "text":
                case "text_readonly":
                case "date_select":
                case "textarea":
                case "password":

                    // Iterate over the criteria
                    $criteria = array_unique(explode("|", $field['validation'] ));
                    foreach($criteria as $criterium) {

                        // Get the correct action.
                        $criterium = $this->_script_criterium($criterium);

                        // Store it in the $rules and $messages array, so we can
                        // use that to build the javascript.
                        if ($criterium!=false) {
                            $rules[$field['name']][] = $criterium;
                            $messages[$field['name']] = $field['error'];
                        }

                    }


                    break;



            }



        }

        // Compose $output with a header, footer, and the results from the various fields.
        $output = "<script type='text/javascript'>\n";
        $output .= "jQuery(function($) {\n";

        $output .= "\tjQuery('#".$this->formname."').validate({\n";
        $output .= "\t\tevent: 'keyup',\n";

        // Add the rules and messages..
        if (count($rules)>0) {
            $outputrules = array();
            $output .= "\t\trules : { \n";
            foreach ($rules as $name=>$rule) {
                $outputrules[] = "\t\t\t'$name': { " . implode(", ", $rule) . " }";
            }
            $output .= implode(",\n", $outputrules). "\n";
            $output .= "\t\t}, \n";

            $output .= "\t\tmessages : { \n";
            foreach ($messages as $name=>$message) {
                $outputmessages[] = "\t\t\t'$name': \"". htmlentities($message, ENT_QUOTES, 'UTF-8') . "\"";
            }
            $output .= implode(",\n", $outputmessages). "\n";
            $output .= "\t\t} \n";
        }


        $output .= "\n\t});\n});";
        $output .= "\n</script>\n";

        //echo ("<pre>" .htmlentities($output) . "</pre>");

        return $output;

    }


    /**
     * This function renders a single field. Normally you don't need
     * to call this function manually
     *
     * @see render
     * @param string $field
     *
     */
    function renderfield($field) {


        // prepare error messages..
        if (($field['show_error'] == 1) && ($field['error'] != "")) {
            $error = $this->error;
            $error = str_replace("%error%",$field['error'],  $error);
            $error = str_replace("%name%",$field['name'],  $error);

            $field['error'] = $error;
            $field['haserror'] = $this->haserror;
        } else {
            $noterror = $this->noterror;
            $noterror = str_replace("%noterror%", $field['noterror'],  $noterror);
            $noterror = str_replace("%name%", $field['name'],  $noterror);

            $field['error'] = $noterror;
            $field['haserror'] = "";
        }

        // prepare 'is required' notification..
        if ($field['isrequired'] == 1) {
            $field['isrequired'] = $this->isrequired;
        } else {
            $field['isrequired'] = "";

        }

        // Perhaps override the value with the one from _POST
        if (isset($field['post_value'])) {
            $field['value'] = $field['post_value'];
        }

        $field['addslashes-value'] = str_replace('&', '&amp;', $field['value']);
        $field['addslashes-value'] = str_replace('<', '&lt;', $field['addslashes-value']);
        $field['addslashes-value'] = str_replace('>', '&gt;', $field['addslashes-value']);
        $field['addslashes-value'] = str_replace('"', '&quot;', $field['addslashes-value']);
        $field['addslashes-value'] = str_replace("'", '&#39;', $field['addslashes-value']);

        $field['value'] = str_replace('"', '&quot;', $field['value']);

        // Prepare radio buttons..
        if ($field['type'] == "radio") {

            $field['elements'] = "";
            foreach ($field['options'] as $value => $label) {

                $checked = ( ($value==$field['value']) ? "checked='checked'" : "" );

                $temp_elem = $this->html['radio_element'];
                $temp_elem = str_replace('%style%', $field['style'], $temp_elem);
                $temp_elem = str_replace('%name%', $field['name'], $temp_elem);
                $temp_elem = str_replace('%value%', $value, $temp_elem);
                $temp_elem = str_replace('%label%', $label, $temp_elem);
                $temp_elem = str_replace('%formname%', $this->formname, $temp_elem);
                $temp_elem = str_replace('%checked%', $checked, $temp_elem);

                $field['elements'] .= $temp_elem;
            }
        }


        // Prepare radiogrid buttons..
        if ($field['type'] == "radiogrid") {

            $field['elements'] = "<table border='0' cellspacing='0' cellpadding='4' >\n";

            // Topmost row with headers (values)..
            $field['elements'] .= "<tr>\n<td>&nbsp;</td>\n";

            foreach ($field['values'] as $key => $item) {
                $field['elements'] .= "<th>$item</th>";
            }

            // Other rows with options and values.
            foreach ($field['options'] as  $label => $value) {

                $field['elements'] .= "<tr>\n<td>$label</td>\n";

                foreach ($field['values'] as $key => $item) {

                    //$label = $this->_safeString($label, true);
                    //$item = $this->_safeString($item, true);

                    $checked = ( ($value==$item) ? "checked='checked'" : "" );

                    // echo "[ $item => $checked ]";

                    $temp_elem = "<td>".$this->html['radio_element']."</td>";
                    $temp_elem = str_replace('%style%', $field['style'], $temp_elem);
                    $temp_elem = str_replace('%name%', $field['name']."[".$label."]", $temp_elem);
                    $temp_elem = str_replace('%value%', $item, $temp_elem);
                    $temp_elem = str_replace('%label%', '', $temp_elem);
                    $temp_elem = str_replace('%formname%', $this->formname, $temp_elem);
                    $temp_elem = str_replace('%checked%', $checked, $temp_elem);

                    $field['elements'] .= $temp_elem;
                }

                $field['elements'] .= "</tr>\n";

            }

            $field['elements'] .= "</table>\n";

        }



        // Prepare checkboxgrid buttons..
        if ($field['type'] == "checkboxgrid") {

            $field['elements'] = "<table border='0' cellspacing='0' cellpadding='4' >\n";

            // Topmost row with headers (values)..
            $field['elements'] .= "<tr>\n<td>&nbsp;</td>\n";

            foreach ($field['values'] as $key => $item) {
                $field['elements'] .= "<th>$item</th>";
            }

            // Other rows with options and values.
            foreach ($field['options'] as  $id => $value) {

                if (isset($value['label'])) {
                    $label = $value['label'];
                } else {
                    $label = $id;
                }

                $field['elements'] .= "<tr>\n<td>$label</td>\n";

                foreach ($field['values'] as $key => $item) {

                    if (is_array($value['grid'])) {
                        $checked = ( in_array($item, $value['grid']) ? "checked='checked'" : "" );
                    } else {
                        $checked = ( ($value['grid']==$item) ? "checked='checked'" : "" );
                    }

                    $temp_elem = "<td>".$this->html['checkboxgrid_element']."</td>";
                    $temp_elem = str_replace('%style%', $field['style'], $temp_elem);
                    $temp_elem = str_replace('%name%', $field['name']."[".$id."][]", $temp_elem);
                    $temp_elem = str_replace('%value%', $item, $temp_elem);
                    $temp_elem = str_replace('%label%', '', $temp_elem);
                    $temp_elem = str_replace('%formname%', $this->formname, $temp_elem);
                    $temp_elem = str_replace('%checked%', $checked, $temp_elem);

                    $field['elements'] .= $temp_elem;
                }

                $field['elements'] .= "</tr>\n";

            }

            $field['elements'] .= "</table>\n";

        }


        // prepare checkboxes..
        if ($field['type'] == "checkbox") {
            $field['checked'] = ( ($field['value']==1) ? "checked='checked'" : "" );
        }


        // prepare select..
        if ( ($field['type'] == "select") || ($field['type'] == "color_select") ) {

            if ($field['multiple']) {
                $field['multiple'] = "multiple='multiple'";
                $field['name'] .= '[]';

                // If we have a multiple select, but the value is a string, separate
                // it by ',' into an array..
                if (is_string($field['value']) && strlen($field['value'])>0) {
                    $field['value'] = explode(',', $field['value']);
                }

                $jqueryselector =  $field['name'];
                $jqueryselector = "'#".str_replace(array('#', '.', '[', ']'), array('\\\\#', '\\\\.', '\\\\[', '\\\\]'), $jqueryselector)."'";
                $str = '<br />%s <a href="javascript:void($('.$jqueryselector.').children().attr(\'selected\', true).parent().trigger(\'click\'));"> %s</a> / ';
                $str .= '<a href="javascript:void($('.$jqueryselector.').children().removeAttr(\'selected\').parent().trigger(\'click\'));"> %s</a>.';
                $field['multiple_selectors'] = sprintf($str, __('Select'), __('all'), __('none'));
            } else {
                $field['multiple_selectors'] = '';

            }

            $field['elements'] = "";

            if (isset($field['firstoption'])) {
                $field['elements'] .= "<option value='' >".$field['firstoption']."</option>\n";
                $field['elements'] .= "<option value='' disabled='disabled' >--------------</option>\n";
            }

            foreach ($field['options'] as $value => $label) {

                if ( is_array($field['value'])) {
                    // Multiple select
                    if (in_array($value, $field['value'])) {
                        $selected = "selected='selected'";
                    } else {
                        $selected = "";
                    }
                } else if ( ($value==$field['value']) && ($field['value']!=='') ) {
                    // Single select (dropdown)
                    $selected = "selected='selected'";
                } else {
                    // Not selected..
                    $selected = "";
                }

                if ($field['type'] == "select") {
                    $temp_elem = $this->html['select_element'];
                } else {
                    $temp_elem = $this->html['color_select_element'];
                }

                // If the value is 'disabled', we disable this option.
                if(is_string($value) && ($value=='disabled')) {
                    $value="";
                    $label="--------------";
                    $disabled = "disabled='disabled'";
                } else {
                    $disabled = "";
                }

                $temp_elem = str_replace('%style%', $field['style'], $temp_elem);
                $temp_elem = str_replace('%name%', $field['name'], $temp_elem);
                $temp_elem = str_replace('%value%', $value, $temp_elem);
                $temp_elem = str_replace('%label%', $label, $temp_elem);
                $temp_elem = str_replace('%formname%', $this->formname, $temp_elem);
                $temp_elem = str_replace('%selected%', $selected, $temp_elem);
                $temp_elem = str_replace('%disabled%', $disabled, $temp_elem);

                $field['elements'] .= $temp_elem;
            }
        }



        /**
         * prepare add_select
         */
        if ( $field['type'] == "add_select" ) {

            $field['elements'] = "";
            $field['unselected-elements'] = "";

            $GLOBALS['add_select_value'] = array_flip($field['value']);

            uksort($field['options'], "add_select_sort");

            foreach ($field['options'] as $value => $label) {

                $temp_elem = $this->html['select_element'];

                $temp_elem = str_replace('%name%', $field['name'], $temp_elem);
                $temp_elem = str_replace('%value%', $value, $temp_elem);
                $temp_elem = str_replace('%label%', $label, $temp_elem);
                $temp_elem = str_replace('%formname%', $this->formname, $temp_elem);
                $temp_elem = str_replace('%selected%', $selected, $temp_elem);

                if (is_array($field['style'])) {
                    $temp_elem = str_replace('%style%', $field['style'][$value], $temp_elem);
                } else {
                    $temp_elem = str_replace('%style%', $field['style'], $temp_elem);
                }

                if (in_array($value, $field['value'])) {
                    $field['elements'] .= $temp_elem;
                } else {
                    $field['unselected-elements'] .= $temp_elem;
                }

            }
        }
        // End of 'prepare add_select'


        $output = $this->html[ $field['type'] ];

        foreach ($field as $key => $value) {

            // Skip substitution if $value is an array..
            if (is_array($value)) { continue; }

            $output = str_replace('%'.strval($key).'%',  $value, $output);

        }
        $output = str_replace('%formname%', $this->formname, $output);
        $output = str_replace("%tabindex%", $this->tabindex++, $output);

        // Hidden fields are collected and inserted outside the table at the end.
        if (($field['type'] == "hidden") || ($field['type'] == "csrf")) {
            $this->hidden_fields[] = $output;
            return;
        } else {
            return $output."\n\n";
        }

    }


    /**
     * Trigger an error in a field
     *
     * @param string $name
     * @param string $error
     */
    function seterror($name, $error="") {

        foreach($this->fields as $key => $field) {
            if ($field['name'] == $name) {
                $this->fields[$key]['show_error'] = true;
                if ($error != "") {
                    $this->fields[$key]['error'] = $error;
                    $this->fields[$key]['show_error'] = true;
                }
            }
        }

    }

    /**
     * Clear (remove) an error from a certain field
     *
     * @param string $name
     */
    function clearerror($name) {

        foreach($this->fields as $key => $field) {
            if ($field['name'] == $name) {
                $this->fields[$key]['show_error'] = false;
            }
        }
    }



    /**
     * Validate the form using the set criteria.
     *
     * @return integer
     */
    function validate() {


        if (count($_POST)<1) {
            return FORM_NOTPOSTED;
        }

        $error = 0;

        foreach ($this->fields as $key => $field) {

            //debug("validate: ".$field['name'] . " - " . $field['validation']);
            $error += $this->_validate_field($key, $field);

        }

        if ($error == 0) {
            return FORM_OK;
        } else {
            return FORM_HASERRORS;
        }

    }


    /**
     * Return an array with the fields that have an error.
     *
     * @return array
     */
    function geterrors() {

        $errors = array();

        foreach ($this->fields as $field) {
            if ($field['show_error']==1) {
                $errors[] = $field['name'];
            }
        }

        return $errors;

    }

    /**
     * Validate a certain field..
     *
     * @param string $key
     * @param $string $field
     * @return integer
     */
    function _validate_field($key, $field) {

        $error = 0;

        if ( ($field['validation']!="") ) {

            $criteria = explode("|", $field['validation']);

            foreach ($criteria as $criterium) {

                // Special case: 'ifany' - if value is empty, no further validation
                // will be done..
                if (($criterium == "ifany") && ($field['post_value']=="")) {
                    // echo "ifany";
                    break;
                }

                if (!$this->_validate_criterium($criterium, $field['post_value'])) {
                    $this->fields[$key]['show_error'] = true;
                    // debug("error in: ".$this->fields[$key]['name'] . " = " . $field['post_value'] . " (not $criterium)");
                    $error++;
                }
            }

        }

        // If the error was set outside of the validation..
        if ($this->fields[$key]['show_error'] != "") {
            $error++;
        }

        return $error;

    }


    /**
     * Validate a single criteria in a certain field
     *
     * @param string $criterium
     * @param mixed $value
     * @return boolean
     */
    function _validate_criterium($criterium, $value) {

        if (strpos($criterium, "=")>0) {
            list($criterium, $cri_val) = explode("=", $criterium);
        }

        switch($criterium) {

            case "ifany":
                return 1;

            // Check to see if $value is a string
            case "string":
                return is_string($value);

            // Check to see if $value is an integer.
            case "integer":
                return is_numeric($value);
            case "float":
                return is_numeric($value);

            // Check to see if length of $value is ok..
            case "minlen":
                return (strlen($value)>=$cri_val);
            case "maxlen":
                return (strlen($value)<=$cri_val);

            // Check to see if value of $value is ok..
            case "min":
                return ($value>=$cri_val);
            case "max":
                return ($value<=$cri_val);

            case "equal":
                return ( ($value == $cri_val) || ( ($value===0) && ($cri_val===0) ));

            case "any":
                return ($value != "");

            case "sameas":
                foreach($this->fields as $temp_field) {
                    //debug("sameas 1 : ".$temp_field['name'] ." == ". $cri_val);
                    //debug("sameas 2 : ".$temp_field['post_value'] ." == ". $value);
                    if (($temp_field['name'] == $cri_val) && ($temp_field['post_value']==$value)) {
                        return 1;
                    }
                }

                // special case for ajax validation: if $_GET['sameas'] is set, use that
                if (isset($_GET['sameas'])) {
                    return ($value==$_GET['sameas']) ? 1 : 0;
                }

                return 0;



            case "or":
                foreach($this->fields as $temp_field) {
                    //debug("sameas 1 : ".$temp_field['name'] ." == ". $cri_val);
                    //debug("sameas 2 : ".$temp_field['post_value'] ." == ". $value);
                    if ($temp_field['name'] == $cri_val) {

                        if ( ($temp_field['post_value'] != "") || ($value!="") ) {
                            return 1;
                        } else{
                            return 0;
                        }
                    }
                }

                // If the 'other' field doesn't exist, return true if 'this' field is set:
                return ($value!="");


            case "email":
                return $this->_isEmail( $value );

            case "phonenumber":
                if (preg_match("/^([0-9\(\) +-]*)$/",$value,$match)) {
                    return 1;
                } else {
                    return 0;
                }

            case "zipcodenl":
                return preg_match('/^([0-9]{4}\s?[a-z]{2})$/i', $value);

            case "upload":
                return $this->upload_success;

            case "safestring":
                return ($value == safeString($value, true));


            case 'datetime':
                 return preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2})/', $value);

            // unknown criterium..
            default:
                return false;

        }

    }



    /**
     * Get the values to use in javascript validation.
     *
     * @param string $criterium
     * @return string
     */
    function _script_criterium($criterium) {

        if (strpos($criterium, "=")>0) {
            list($criterium, $cri_val) = explode("=", $criterium);
        }

        switch($criterium) {


            //case "ifany":
            //  return 1;

            // Check to see if $value is a string
            case "string":
            case "any":
                return "required:true";

            // Check to see if $value is an integer.
            case "integer":
                return "digits:true";
            case "float":
                return "number:true";

            // Check to see if length of $value is ok..
            case "minlen":
                return "minlength:$cri_val";
            case "maxlen":
                return "maxlength:$cri_val";

            // Check to see if value of $value is ok..
            case "min":
                return "min:$cri_val";
            case "max":
                return "max:$cri_val";

            //case "equal":
            //  return "equalTo:'$cri_val'";

            case "any":
                return "required:true";


            case "sameas":
                return "equalTo:'#". $cri_val ."'";


/*
            case "or":
                foreach($this->fields as $temp_field) {
                    //debug("sameas 1 : ".$temp_field['name'] ." == ". $cri_val);
                    //debug("sameas 2 : ".$temp_field['post_value'] ." == ". $value);
                    if ($temp_field['name'] == $cri_val) {

                        if ( ($temp_field['post_value'] != "") || ($value!="") ) {
                            return 1;
                        } else{
                            return 0;
                        }
                    }
                }

                // If the 'other' field doesn't exist, return true if 'this' field is set:
                return ($value!="");
*/


            case "email":
                return "email:true";


/*
            case "phonenumber":
                if (preg_match("/^([0-9\(\) +-]*)$/",$value,$match)) {
                    return 1;
                } else {
                    return 0;
                }
*/

/*
            case "zipcodenl":
                return preg_match('/^([0-9]{4}\s?[a-z]{2})$/i', $value);
*/

/*
            case "upload":
                return $this->upload_success;
*/

/*
            case 'datetime':
                 return preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2})/', $value);
*/

            // unknown criterium..
            default:
                return false;

        }

    }


    function getvalues() {

        $result = array();

        foreach ($this->fields as $field) {

            // Don't return info, header, hr or custom 'fields'.
            if (!in_array($field['type'], array('info', 'header', 'hr', 'custom'))) {

                // Use the 'post_value' if it's set, else the 'value'
                if (isset($field['post_value'])) {
                   $result[ $field['name'] ] = $field['post_value'];
                } else {
                    $result[ $field['name'] ] = $field['value'];
               }

            }
        }

        return $result;


    }


    function setvalues($values) {

        foreach ($this->fields as $key => $field) {
            if (isset($values[ $field['name'] ] )) {

                $value = $values[ $field['name'] ];
                $value = str_replace("'", "&#039;", $value);
                $value = str_replace('"', "&quot;", $value);

                $this->fields[ $key ]['value'] = $value;
                unset($this->fields[ $key ]['post_value']);
            }
        }

        return $result;

    }


    function setvalue($fieldname, $value) {

        foreach ($this->fields as $key => $field) {
            if ($field['name']==$fieldname) {

                $value = str_replace("'", "&#039;", $value);
                $value = str_replace('"', "&quot;", $value);

                $this->fields[ $key ]['value'] = $value;
                unset($this->fields[ $key ]['post_value']);
            }
        }

        return $result;

    }




    function clearpost() {
        unset ($_POST);
    }


    function submitlocation($str) {
        $this->submitloc = " ".$str;
    }


    /**
     * Checks if the text is a valid email address.
     *
     * Given a chain it returns true if $theAdr conforms to RFC 2822.
     * It does not check the existence of the address.
     * Suppose a mail of the form
     *  <pre>
     *  addr-spec     = local-part "@" domain
     *  local-part    = dot-atom / quoted-string / obs-local-part
     *  dot-atom      = [CFWS] dot-atom-text [CFWS]
     *  dot-atom-text = 1*atext *("." 1*atext)
     *  atext         = ALPHA / DIGIT /    ; Any character except controls,
     *        "!" / "#" / "$" / "%" /      ;  SP, and specials.
     *        "&" / "'" / "*" / "+" /      ;  Used for atoms
     *        "-" / "/" / "=" / "?" /
     *        "^" / "_" / "`" / "{" /
     *        "|" / "}" / "~" / "." /
     * </pre>
     *
     * @param string $theAdr
     * @return boolean
     */
    function _isEmail( $theAdr ) {

        // Use an existing "isemail" function if it exists 
        // (as in PivotX) to ensure consistent results.
        if (function_exists('isemail')) {
            return isEmail($theAdr );
        }

        // default
        $result = FALSE;

        // go ahead
        if(( ''!=$theAdr )||( is_string( $theAdr ))) {
            $mail_array = explode( '@',$theAdr );
        }
        if( !is_array( $mail_array )) { return FALSE; }
        if( 2 == count( $mail_array )) {
            $localpart = $mail_array[0];
            $domain_array  = explode( '.',$mail_array[1] );
        } else {
            return FALSE;
        }
        if( !is_array( $domain_array ))  { return FALSE; }
        if( 1 == count( $domain_array )) { return FALSE; }

        /* relevant info:
         $localpart contains atext (local part of address)
         $domain_array  contains domain parts of address
            and last one must be at least 2 chars 
         */
        $domain_toplevel = array_pop( $domain_array );
        if(is_string($domain_toplevel) && (strlen($domain_toplevel) > 1)) {
            // put back
            $domain_array[] = $domain_toplevel;
            $domain = implode( '',$domain_array );
            // now we have two string to test
            // $domain and $localpart
            $domain        = preg_replace( "/[a-z0-9]/i","",$domain );
            $domain        = preg_replace( "/[-|\_]/","",$domain );
            $localpart = preg_replace( "/[a-z0-9]/i","",$localpart);
            $localpart = preg_replace(
                "#[-.|\!|\#|\$|\%|\&|\'|\*|\+|\/|\=|\? |\^|\_|\`|\{|\||\}|\~]#","",$localpart);
            // If there are no characters left in localpart or domain, the
            // email address is valid.
            if(( '' == $domain )&&( '' == $localpart )) { $result = TRUE; }
        }
        return $result;
    }

    /**
    * Upload files in a directory.
    * @return bool
    * @param $pFile File to upload.
    * @param $pDirectory Upload directory.
    * @param $pMaxFileSize Maximum filesize. Default value = 2048 kb.
    */
    function handleUpload($pFile, $pDirectory = './', $pMaxFileSize = 2048, $pAllowedTypes, $pFieldName) {

        debug('handleupload: '.$pFile . " = " . $_FILES[$pFieldName]['name'] );

        if ($pFile != $_FILES[$pFieldName]['name']) {
            // we shouldn't handle this one right now..
            debug("skip");
            return false;
        }

        if ($pFile['name']=="") {
            // No file uploaded..
            return false;
        }

        // Select which folder to put file in

        umask(0);

        $pDirectory = str_replace('%firstletter%', strtoupper(substr($this->_safeString($pFile),0,1)), $pDirectory);
        $pDirectory = str_replace('%month%', date("Y-m"), $pDirectory);

        if (file_exists($pDirectory) === FALSE) {

            // Directory does not exists, try to create it
            if (makeDir($pDirectory) === FALSE) {
                debug("couldn't make '$pDirectory'.");
                return(FALSE);
            }

        }

        if (is_writable($pDirectory) === FALSE) {
            if (chmod($pDirectory, 0777) === FALSE) {
                return(FALSE);
            }
        }

        // Directory is writable
        // Get from config file which extensions are allowed

        $aAllowedTypes = explode(',', $pAllowedTypes);
        $uploadFileExtension = strtolower($this->_getExtension($pFile));

        if (in_array($uploadFileExtension, $aAllowedTypes) === FALSE) {
           $this->seterror($pFieldName, 'This extension is not allowed.');
           return(FALSE);
        }

        /*
        // Check filesize
        if ($pMaxFileSize > MAX_FILE_SIZE) {

            // File too big
            $this->seterror($pFieldName, 'Filesize too big. Allowed filesize is '.  MAX_FILE_SIZE . ' kb.');
            debug("failed: $pFile - Filesize too big. Allowed filesize is ".    MAX_FILE_SIZE . " kb.");
            return(FALSE);
        }
        */

        $pFile = $this->_safeString($pFile, true);


        // Does the file already exists?
        if (file_exists($pDirectory.'/'. $pFile) === TRUE) {

            // Get string out of filename before the last dot
            $filename = substr($pFile, 0, strrpos($pFile, '.'));

            // New filename with timestamp
            $pFile = $filename . '_' . date('YmdHis') . '.' . $this->_getExtension($pFile);
        }

        if (is_uploaded_file($_FILES[$pFieldName]['tmp_name']) === FALSE) {

            debug_printr($_FILES[$pFieldName]);

            $this->seterror($pFieldName, 'File upload failed.');
            debug("failed: $pFile - File upload failed (1).");
            return(FALSE);
        }

        if (move_uploaded_file($_FILES[$pFieldName]['tmp_name'], $pDirectory.'/'.$pFile) === FALSE) {
           $this->seterror($pFieldName, 'File upload failed.');
           debug("failed: $pFile - File upload failed (2).");
           return(FALSE);
        } else {
            chmod($pDirectory.'/'.$pFile, 0777);
            $this->lastuploadfile = $pFile;
            $this->uploads[$pFieldName] = array('dir' => $pDirectory, 'filename' => $pFile);
        }

        // Everything went fine.
        debug("success: $pDirectory $pFile");
        return(TRUE);

    }



    /**
     * Set whether or not to use javascript validation.
     *
     * @param boolean $val
     */
    function use_javascript($val) {
        $this->use_javascript = $val;
    }

    /**
     * add_select_sort
     */
    function add_select_sort($a,$b) {

        $ref = $GLOBALS['add_select_value'];

        if (isset($ref[$a]) && isset($ref[$b])) {
            return ($ref[$a] > $ref[$b]) ? 1 : -1;
        } elseif (isset($ref[$a])) {
            return 1;
        } elseif (isset($ref[$b])) {
            return -1;
        } else {
            return ($a > $b) ? 1 : -1;
        }

    }

    function _safeString($str, $strict=FALSE) {

        $str = strip_tags($str);

        $str = strtr($str, "\xA1\xAA\xBA\xBF\xC0\xC1\xC2\xC3\xC5\xC7\xC8\xC9\xCA\xCB\xCC\xCD".
            "\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD8\xD9\xDA\xDB\xDD\xE0\xE1\xE2\xE3\xE5\xE7\xE8".
            "\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF8\xF9\xFA\xFB\xFD\xFF",
            "!ao?AAAAACEEEEIIIIDNOOOOOUUUYaaaaaceeeeiiiidnooooouuuyy");
    
        $str = strtr($str, array("\xC4"=>"Ae", "\xC6"=>"AE", "\xD6"=>"Oe", "\xDC"=>"Ue", "\xDE"=>"TH",
            "\xDF"=>"ss", "\xE4"=>"ae", "\xE6"=>"ae", "\xF6"=>"oe", "\xFC"=>"ue", "\xFE"=>"th"));

        $str=str_replace("&amp;", "", $str);

        if ($strict) {
            $str=str_replace(" ", "_", $str);
            $str=strtolower(preg_replace("/[^a-z0-9_.]/i", "", $str));
        } else {
            $str=preg_replace("/[^a-z0-9 _.,-]/i", "", $str);
        }
        return $str;
    }


    /**
     * Gets the extension (if any) of a filename.
     *
     * @param string $file
     * @return string
     */
    function _getExtension($file) {
        $pos=strrpos($file, ".");
        if (is_string ($pos) && !$pos) {
            return "";
        } else {
            $ext=substr($file, $pos+1);
            return $ext;
        }
    }




}



?>

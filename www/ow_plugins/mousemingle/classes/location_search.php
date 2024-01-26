<?php 

class MOUSE_CLASS_LocationSearch extends GOOGLELOCATION_CLASS_LocationSearch
{
    public function __construct($name)
    {
        parent::__construct($name);
    }

    public function renderInput( $params = null )
    {
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('googlelocation')->getStaticJsUrl() . 'location.js', "text/javascript", GOOGLELOCATION_BOL_LocationService::JQUERY_LOAD_PRIORITY + 1);

        /* OW::getDocument()->addOnloadScript(' $( document ).ready( function(){ window.googlemap_location_search = new OW_GoogleMapLocation( ' . json_encode($this->getName()) . ','
                . ' ' . json_encode($this->getId()) . ', '.  json_encode(GOOGLELOCATION_BOL_LocationService::getInstance()->getCountryRestriction()).' );
                                             window.googlemap_location_search.initialize(); }); '); */

        $params = array(
            'region' => $this->region,
            'countryRestriction' => GOOGLELOCATION_BOL_LocationService::getInstance()->getCountryRestriction()
        );

        OW::getDocument()->addOnloadScript(' GOOGLELOCATION_INIT_SCOPE.push( function(){ window.googlemap_location_search = new OW_GoogleMapLocation( ' . json_encode($this->getName()) . ', ' . json_encode($this->getId()) . ', null );
                                             window.googlemap_location_search.initialize(' . json_encode($params) . '); }); ');
        
        $attribute = array(
            'type' => 'hidden',
            'name' => $this->getName() . '[address]',
            'value' => !empty($this->value['address']) ? $this->escapeValue($this->value['address']) : '');

        $html = UTIL_HtmlTag::generateTag('input', $attribute);

        $attribute = array(
            'type' => 'hidden',
            'name' => $this->getName() . '[latitude]',
            'value' => !empty($this->value['latitude']) ? $this->escapeValue($this->value['latitude']) : '');

        $html .= UTIL_HtmlTag::generateTag('input', $attribute);

        $attribute = array(
            'type' => 'hidden',
            'name' => $this->getName() . '[longitude]',
            'value' => !empty($this->value['longitude']) ? $this->escapeValue($this->value['longitude']) : '');

        $html .= UTIL_HtmlTag::generateTag('input', $attribute);

        $attribute = array(
            'type' => 'hidden',
            'name' => $this->getName() . '[northEastLat]',
            'value' => !empty($this->value['latitude']) ? $this->escapeValue($this->value['northEastLat']) : '');

        $html .= UTIL_HtmlTag::generateTag('input', $attribute);

        $attribute = array(
            'type' => 'hidden',
            'name' => $this->getName() . '[northEastLng]',
            'value' => !empty($this->value['longitude']) ? $this->escapeValue($this->value['northEastLng']) : '');

        $html .= UTIL_HtmlTag::generateTag('input', $attribute);

        $attribute = array(
            'type' => 'hidden',
            'name' => $this->getName() . '[southWestLat]',
            'value' => !empty($this->value['latitude']) ? $this->escapeValue($this->value['southWestLat']) : '');

        $html .= UTIL_HtmlTag::generateTag('input', $attribute);

        $attribute = array(
            'type' => 'hidden',
            'name' => $this->getName() . '[southWestLng]',
            'value' => !empty($this->value['longitude']) ? $this->escapeValue($this->value['southWestLng']) : '');

        $html .= UTIL_HtmlTag::generateTag('input', $attribute);

        $attribute = array(
            'type' => 'hidden',
            'name' => $this->getName() . '[json]',
            'value' => !empty($this->value['json']) ? $this->escapeValue($this->value['json']) : '');

        $html .= UTIL_HtmlTag::generateTag('input', $attribute);

        // location wrapper
        $html .= "<div class='location_wrapper'>";

        $attribute = array(
            'type' => 'text',
            'name' => $this->getName() . '[distance]',
            'class' => 'ow_googlelocation_search_distance',
            'value' => !empty($this->value['distance']) ? $this->escapeValue($this->value['distance']) : '');
        $distanceLabel = OW::getLanguage()->text('googlelocation', 'miles_from');
        if ( OW::getConfig()->getValue('googlelocation', 'distance_units') == GOOGLELOCATION_BOL_LocationService::DISTANCE_UNITS_MILES )
        {
            $distanceLabel = OW::getLanguage()->text('googlelocation', 'kms_from');
        }
        $capDistanceLabel = ucfirst($distanceLabel);
        
        
        $distanceArr = [10, 20, 30, 40, 50, 60, 80, 100];
        $distanceOptions = null;
        foreach($distanceArr as $distance)
        {
            $selected = $distance == 10 ? 'selected' : '';
            $distanceOptions .= "<option value='{$distance}' {$selected}>{$distance}</option>";
        }
        
        $selectOptionName = $this->getName() . '[distance]';
        
        //<span>" . UTIL_HtmlTag::generateTag('input', $attribute) . "</span>
        // distance wrap
        $html .= "
            <div class='distance_wrap'>
                <div class='ow_googlelocation_search_miles_from'>{$capDistanceLabel}</div>
                <span><select name='{$selectOptionName}' class='ow_googlelocation_search_distance'>
                {$distanceOptions}
                </select></span>
            </div>";




        $attribute = $this->attributes;
        unset($attribute['name']);
        $attribute['value'] = !empty($this->value['address'])  ? $this->value['address'] : '';
        $attribute['class'] .= ' ow_left ow_googlelocation_location_search_input';

        if ( empty($attribute['value']) && $this->hasInvitation )
        {
            $attribute['value'] = $this->invitation;
            $attribute['class'] .= ' invitation';
        }

        $html .= '<div class="googlelocation_address_div">
                    <div class="location_label">'.OW::getLanguage()->text('mekirim', 'search_location').'</div>'
                    .UTIL_HtmlTag::generateTag('input', $attribute).
                    '<div class="googlelocation_address_icon_div">
                        <span id='.json_encode($this->getId().'_icon').' style="'.(!empty($this->value['json']) ? 'display:none': 'display:inline').'" class="ic_googlemap_pin googlelocation_address_icon"></span>
                        <div id='.json_encode($this->getId().'_delete_icon').'  style="'.(empty($this->value['json']) ? 'display:none': 'display:inline').'" class="ow_miniic_delete owm_ic_close_cont googlelocation_delete_icon"></div>
                    </div>
                 </div>';
        
        // close location wrapper
        $html .= "</div>";

        return $html;
    }
}
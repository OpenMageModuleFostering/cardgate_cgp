<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Block_Form_Klarnaaccount extends Mage_Payment_Block_Form
{

	protected function _construct ()
	{
		parent::_construct();
		$this->setTemplate( 'cardgate/cgp/form/klarnaaccount.phtml' );
	}

	/**
	 * Return information payment object
	 *
	 * @return Mage_Payment_Model_Info
	 */
	public function getInfoInstance ()
	{
		return $this->getMethod()->getInfoInstance();
	}

	public function getYear ()
	{
		$yr = 'Year';
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				$yr = 'Jahr';
				break;
			case 'da_DK':
				$yr = 'Ar';
				break;
			case 'fi_FI':
				$yr = 'Vuosi';
				break;
			case 'de_DE':
				$yr = 'Jahr';
				break;
			case 'nl_NL':
				$yr = 'Jaar';
				break;
			case 'nb_NO':
				$yr = 'Ar';
				break;
			case 'sv_SE':
				$yr = 'Ar';
				break;
		}
		
		$str = '<option value="">' . $yr . '</option>';
		for ( $x = date( 'Y' ); $x >= 1900; $x -- ) {
			$str .= '<option value="' . $x . '">' . $x . '</option/>';
		}
		return $str;
	}

	public function getMonth ()
	{
		$str = '<option value="">Month</option>';
		for ( $x = 1; $x < 13; $x ++ ) {
			$str .= '<option value="' . sprintf( "%02d", $x ) . '">' . date( 'F', mktime( 1, 1, 1, $x, 1, 1970 ) ) .
					 '</option>';
		}
		
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				$str = '<option value="">Monat</option>
                        <option value="01">Januar</option>
                        <option value="02">Februar</option>
                        <option value="03">März</option>
                        <option value="04">April</option>
                        <option value="05">Mai</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Dezember</option>';
				break;
			case 'da_DK':
				$str = '<option value="">Måned</option>
                        <option value="01">Januar</option>
                        <option value="02">Februar</option>
                        <option value="03">Marts</option>
                        <option value="04">April</option>
                        <option value="05">Maj</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">December</option>';
				break;
			case 'fi_FI':
				$str = '<option value="">Kuukausi</option>
                        <option value="01">Tammikuu</option>
                        <option value="02">Helmikuu</option>
                        <option value="03">Maaliskuu</option>
                        <option value="04">Huhtikuu</option>
                        <option value="05">Toukokuu</option>
                        <option value="06">Kesäkuu</option>
                        <option value="07">Heinäkuu</option>
                        <option value="08">Elokuu</option>
                        <option value="09">Syyskuu</option>
                        <option value="10">Lokakuu</option>
                        <option value="11">Marraskuu</option>
                        <option value="12">Joulukuu</option>';
				break;
			case 'de_DE':
				$str = '<option value="">Monat</option>
                        <option value="01">Januar</option>
                        <option value="02">Februar</option>
                        <option value="03">März</option>
                        <option value="04">April</option>
                        <option value="05">Mai</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Dezember</option>';
				break;
			case 'nl_NL':
				$str = '<option value="">Maand</option>
                        <option value="01">januari</option>
                        <option value="02">februari</option>
                        <option value="03">maart</option>
                        <option value="04">april</option>
                        <option value="05">mei</option>
                        <option value="06">juni</option>
                        <option value="07">juli</option>
                        <option value="08">augustus</option>
                        <option value="09">september</option>
                        <option value="10">oktober</option>
                        <option value="11">november</option>
                        <option value="12">december</option>';
				break;
			case 'nb_NO':
				$str = '<option value="">Måned</option>
                        <option value="01">Januar</option>
                        <option value="02">Februar</option>
                        <option value="03">Mars</option>
                        <option value="04">April</option>
                        <option value="05">Mai</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>';
				break;
			case 'sv_SE':
				$str = '<option value="">Månad</option>
                        <option value="01">januari</option>
                        <option value="02">februari</option>
                        <option value="03">mars</option>
                        <option value="04">april</option>
                        <option value="05">maj</option>
                        <option value="06">juni</option>
                        <option value="07">juli</option>
                        <option value="08">augusti</option>
                        <option value="09">september</option>
                        <option value="10">oktober</option>
                        <option value="11">november</option>
                        <option value="12">december</option>';
				break;
		}
		
		return $str;
	}

	public function getDay ()
	{
		$day = 'Day';
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				$day = 'Tag';
				break;
			case 'da_DK':
				$day = 'Dag';
				break;
			case 'fi_FI':
				$day = 'Päivä';
				break;
			case 'de_DE':
				$day = 'Tag';
				break;
			case 'nl_NL':
				$day = 'Dag';
				break;
			case 'nb_NO':
				$day = 'Dag';
				break;
			case 'sv_SE':
				$day = 'Dag';
				break;
		}
		
		$str = '<option value="">' . $day . '</option>';
		for ( $x = 1; $x <= 31; $x ++ ) {
			$str .= '<option value="' . sprintf( "%02d", $x ) . '">' . sprintf( "%02d", $x ) . '</option>';
		}
		return $str;
	}

	public function getLanguage ()
	{
		$str = '<option value="de">Austrian</option>
                <option value="da">Danisch</option>
                <option value="fi">Finnisch</option>
                <option selected="selected" value="de">German</option>
                <option value="nl">Dutch</option>
                <option value="nb">Norwegian</option>
                <option value="sv">Swedisch</option>';
		
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				$str = '<option selected="selected" value="de">Österreichisch</option>
                        <option value="da">Dänisch</option>
                        <option value="fi">Finnisch</option>
                        <option value="de">Deutsch</option>
                        <option value="nl">Holländisch</option>
                        <option value="nb">Norwegisch</option>
                        <option value="sv">Schwedisch</option>';
				break;
			case 'da_DK':
				$str = '<option value="de">østrigsk</option>
                        <option selected="selected" value="da">Dansk</option>
                        <option value="fi">Finsk</option>
                        <option value="de">Tysk</option>
                        <option value="nl">Hollandsk</option>
                        <option value="nb">Norwegian</option>
                        <option value="sv">Svensk</option>';
				break;
			case 'fi_FI':
				$str = '<option value="de">østrigsk</option>
                        <option value="da">Dansk</option>
                        <option selected="selected" value="fi">Finsk</option>
                        <option value="de">German</option>
                        <option value="nl">Hollandsk</option>
                        <option value="nb">Norja</option>
                        <option value="sv">Ruotsi</option>';
				break;
			case 'de_DE':
				$str = '<option value="de">Österreichisch</option>
                        <option value="da">Dänisch</option>
                        <option value="fi">Finnisch</option>
                        <option selected="selected" value="de">Deutsch</option>
                        <option value="nl">Holländisch</option>
                        <option value="nb">Norwegisch</option>
                        <option value="sv">Schwedisch</option>';
				break;
			case 'nl_NL':
				$str = '<option value="de">Oostenrijks</option>
                        <option value="da">Deens</option>
                        <option value="fi">Fins</option>
                        <option value="de">Duits</option>
                        <option selected="selected" value="nl">Nederlands</option>
                        <option value="nb">Norweegs</option>
                        <option value="sv">Zweeds</option>';
				break;
			case 'nb_NO':
				$str = '<option value="de">østerriksk</option>
                        <option value="da">Dansk</option>
                        <option value="fi">Finsk</option>
                        <option value="de">Tyske</option>
                        <option value="nl">Dutch</option>
                        <option selected="selected" value="nb">Norsk</option>
                        <option value="sv">Svensk</option>';
				break;
			case 'sv_SE':
				$str = '<option value="de">österrikiska</option>
                        <option value="da">Dansk</option>
                        <option value="fi">Finska</option>
                        <option value="de">Tysk</option>
                        <option value="nl">Holländare</option>
                        <option value="nb">Norska</option>
                        <option selected="selected" value="sv">Svenska</option>';
				break;
		}
		return $str;
	}

	public function getGenderLabel ()
	{
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				return 'Geslecht';
				break;
			case 'da_DK':
				return 'Køn';
				break;
			case 'fi_FI':
				return 'Sukupuoli';
				break;
			case 'de_DE':
				return 'Geschlecht';
				break;
			case 'nl_NL':
				return 'Geslacht';
				break;
			case 'nb_NO':
				return 'Kjønn';
				break;
			case 'sv_SE':
				return 'Kön';
				break;
		}
		return Mage::helper( 'cgp' )->__( 'Gender' );
	}

	public function getMaleLabel ()
	{
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				return 'Mann';
				break;
			case 'da_DK':
				return 'Mand';
				break;
			case 'fi_FI':
				return 'Mies';
				break;
			case 'de_DE':
				return 'Mann';
				break;
			case 'nl_NL':
				return 'Man';
				break;
			case 'nb_NO':
				return 'Mann';
				break;
			case 'sv_SE':
				return 'Man';
				break;
		}
		return Mage::helper( 'cgp' )->__( 'Male' );
	}

	public function getFemaleLabel ()
	{
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				return 'Frau';
				break;
			case 'da_DK':
				return 'Kvinde';
				break;
			case 'fi_FI':
				return 'Nainen';
				break;
			case 'de_DE':
				return 'Frau';
				break;
			case 'nl_NL':
				return 'Vrouw';
				break;
			case 'nb_NO':
				return 'Kvinne';
				break;
			case 'sv_SE':
				return 'Kvinna';
				break;
		}
		return Mage::helper( 'cgp' )->__( 'Female' );
	}

	public function getLanguageLabel ()
	{
		$locale = $this->getLocaleCode();
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				return 'Sprache';
				break;
			case 'da_DK':
				return 'Sprog';
				break;
			case 'fi_FI':
				return 'Kieli';
				break;
			case 'de_DE':
				return 'Sprache';
				break;
			case 'nl_NL':
				return 'Taal';
				break;
			case 'nb_NO':
				return 'Språk';
				break;
			case 'sv_SE':
				return 'Språk';
				break;
		}
	}

	public function getPersonalNumberLabel ()
	{
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				return '';
				break;
			case 'da_DK':
				return 'Personnummer';
				break;
			case 'fi_FI':
				return 'Henkilötunnus';
				break;
			case 'de_DE':
				return '';
				break;
			case 'nl_NL':
				return '';
				break;
			case 'nb_NO':
				return 'Fødselsnummer';
				break;
			case 'sv_SE':
				return 'Personnummer';
				break;
		}
	}

	public function isEU ()
	{
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				return true;
				break;
			case 'da_DK':
				return false;
				break;
			case 'fi_FI':
				return false;
				break;
			case 'de_DE':
				return true;
				break;
			case 'nl_NL':
				return true;
				break;
			case 'nb_NO':
				return false;
				break;
			case 'sv_SE':
				return false;
				break;
		}
	}

	public function beginIsEu ()
	{
		if ( $this->isEU() ) {
			return '';
		} else {
			return '<!--';
		}
	}

	public function endIsEu ()
	{
		if ( $this->isEU() ) {
			return '';
		} else {
			return '-->';
		}
	}

	public function beginIsNotEu ()
	{
		if ( ! $this->isEU() ) {
			return '';
		} else {
			return '<!--';
		}
	}

	public function endIsNotEu ()
	{
		if ( ! $this->isEU() ) {
			return '';
		} else {
			return '-->';
		}
	}

	public function getBirthdayLabel ()
	{
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				return 'Geburtsdatum';
				break;
			case 'da_DK':
				return 'Fødselsdag';
				break;
			case 'fi_FI':
				return 'Syntymäpäivä';
				break;
			case 'de_DE':
				return 'Geburtsdatum';
				break;
			case 'nl_NL':
				return 'Geboortedatum';
				break;
			case 'nb_NO':
				return 'Bursdag';
				break;
			case 'sv_SE':
				return 'Födelsedag';
				break;
		}
		return Mage::helper( 'cgp' )->__( 'Birthday' );
	}

	public function getLocaleCode ()
	{
		$country = Mage::getSingleton( 'checkout/session' )->getQuote()
			->getBillingAddress()
			->getCountry();
		
		switch ( $country ) {
			case 'AT':
				return 'de_AT';
				break;
			case 'DK':
				return 'da_DK';
				break;
			case 'FI':
				return 'fi_FI';
				break;
			case 'DE':
				return 'de_DE';
				break;
			case 'NL':
				return 'nl_NL';
				break;
			case 'NO':
				return 'nb_NO';
				break;
			case 'SE':
				return 'sv_SE';
				break;
			default:
				return 'not_klarna';
		}
	}

	public function getGermanNotice ()
	{
		$locale = $this->getLocaleCode();
		if ( strpos( $locale, 'de_' ) === 0 ) {
			$str = '<br>
                 <div style="float:left;margin-top:5px;">
                     <input
                        type="checkbox"
                        name="payment[cgp][klarna-account-check]"
                        value="0"
                        id="id_cgp_klarna-account-check"
                        class="required-entry"
                        />
                        Mit der Datenverarbeitung der für die Abwicklungdes Rechnungskaufes und einer Identitäts-und Bonitätsprüfung erforderlichen Daten durch Klarna bin ich einverstanden. Meine <span id="consent01"></span> kann ich jederzeit mit Wirkung für die Zukunft widerrufen. Es gelten die AGB des Händlers. <br>
                </div>';
		} else {
			$str = "";
		}
		return $str;
	}

	public function getKlarnaAccountTerms ()
	{
		switch ( $this->getLocaleCode() ) {
			case 'de_AT':
				return '';
				break;
			case 'da_DK':
				break;
			case 'fi_FI':
				break;
			case 'de_DE':
				break;
			case 'nl_NL':
				break;
			case 'nb_NO':
				break;
			case 'sv_SE':
				break;
		}
		
		$str = "new Klarna.Terms.Account({
                        el: 'account01',
                        eid: '" . $this->getKlarnaAccountEid() . "',
                        locale: '" . $this->getLocaleCode() . "',
                        type: 'desktop'
                    });";
		return $str;
	}

	public function getKlarnaAccountConsent ()
	{
		$str = ' ';
		$locale = $this->getLocaleCode();
		if ( strpos( $locale, 'de_' ) === 0 ) {
			$str = "new Klarna.Terms.Consent({  
                        el: 'consent01',
                        eid: '" . $this->getKlarnaAccountEid() . "',
                        locale: '" . $this->getLocaleCode() . "',
                        type: 'desktop'
                    });";
		}
		return $str;
	}

	public function getKlarnaAccountEid ()
	{
		$settings = Mage::getStoreConfig( 'cgp/cgp_klarnaaccount' );
		return $settings['klarna_eid'];
	}

	public function getKlarnaAccountLogoUrl ()
	{
		$str = 'https://cdn.klarna.com/public/images/';
		$country = substr( $this->getLocaleCode(), 3 );
		$str .= $country;
		$str .= '/badges/v1/account/';
		$str .= $country;
		$str .= '_account_badge_std_blue.png?width=125&eid=';
		$str .= $this->getKlarnaAccountEid();
		return $str;
	}
}
<?php

class LIAC_Data {
	
	// ID
	public $id;
	
	// Personal Info
	public $picture_url;
	public $first_name;
	public $last_name;
	public $phone_number;
	public $email;
	public $public_profile_url;
	public $address;
	public $location_name;
	public $location_country_code;
	public $date_of_birth;
	public $industry;
	
	// Experiences and skills (rest of the info)
	public $headline;
	public $summary;
	public $specialties;
	public $current_positions;
	public $past_positions;
	public $all_positions;
	public $educations;
	public $recommendations;
	public $languages;
	public $skills;
	public $certifications;
	public $courses;
	public $interests;
	public $volunteer;
	public $honors_and_rewards;
	public $publications;
	public $last_modified;
	
	// Allow user to get the original/raw data
	public $original_data;
	
	public function __construct( $api_data ) {
		
		$this->id = $api_data->id;
		$this->original_data = $api_data;
		
		if( ! empty( $api_data->pictureUrl ) && "" != $api_data->pictureUrl )
			$this->picture_url = $api_data->pictureUrl;
		if( ! empty( $api_data->firstName ) && "" != $api_data->firstName )
			$this->first_name = $api_data->firstName;
		if( ! empty( $api_data->lastName ) && "" != $api_data->lastName )
			$this->last_name = $api_data->lastName;
		if( ! empty( $api_data->phoneNumbers ) && 0 < $api_data->phoneNumbers->_total )
			$this->phone_number = $api_data->phoneNumbers->values[0]->phoneNumber;
		if( ! empty( $api_data->emailAddress ) && "" != $api_data->emailAddress )
			$this->email = $api_data->emailAddress;
		if( ! empty( $api_data->publicProfileUrl ) && "" != $api_data->publicProfileUrl )
			$this->public_profile_url = $api_data->publicProfileUrl;
		if( ! empty( $api_data->mainAddress ) && "" != $api_data->mainAddress )
			$this->address = $api_data->mainAddress;
		if( ! empty( $api_data->location->name ) && "" != $api_data->location->name )
			$this->location_name = $api_data->location->name;
		if( ! empty( $api_data->location->country->code ) && "" != $api_data->location->country->code )
			$this->location_country_code = $api_data->location->country->code;
		if( ! empty( $api_data->dateOfBirth ) )
			$this->date_of_birth = mktime ( 0, 0, 0, $api_data->dateOfBirth->month, $api_data->dateOfBirth->day, $api_data->dateOfBirth->year );
		if( ! empty( $api_data->industry ) && "" != $api_data->industry )
			$this->industry = $api_data->industry;
		if( ! empty( $api_data->headline ) && "" != $api_data->headline )
			$this->headline = $api_data->headline;
		if( ! empty( $api_data->summary ) && "" != $api_data->summary )
			$this->summary = $api_data->summary;
		if( ! empty( $api_data->specialties ) && "" != $api_data->specialties )
			$this->specialties = $api_data->specialties;
		if( ! empty( $api_data->positions ) && 0 < $api_data->positions->_total ) {
			$this->all_positions = $api_data->positions->values;
			$this->current_positions = array();
			$this->past_positions = array();
			
			foreach( $api_data->positions->values as $position ) {
				
				if( $position->isCurrent ) {
					$this->current_positions[] = $position;
				} else {
					$this->past_positions[] = $position;
				}
				
			}	
		}
		if( ! empty( $api_data->educations ) && 0 < $api_data->educations->_total )
			$this->educations = $api_data->educations->values;
		if( ! empty( $api_data->recomendationsReceived ) && 0 < $api_data->recommendationsReceived->_total )
			$this->recommendations = $api_data->recommendationsReceived->values;
		if( ! empty( $api_data->languages ) && 0 < $api_data->languages->_total )
			$this->languages = $api_data->languages->values;
		if( ! empty( $api_data->skills ) && 0 < $api_data->skills->_total )
			$this->skills = $api_data->skills->values;
		if( ! empty( $api_data->certifications ) && 0 < $api_data->certifications->_total )
			$this->certifications = $api_data->certifications->values;
		if( ! empty( $api_data->courses ) && 0 < $api_data->courses->_total )
			$this->courses = $api_data->courses->values;
		if( ! empty( $api_data->interests ) && 0 < $api_data->interests->_total )
			$this->interests = $api_data->interests->values;
		if( ! empty( $api_data->volunteer ) && 0 < $api_data->volunteer->_total )
			$this->volunteer = $api_data->volunteer->values;
		if( ! empty( $api_data->honorsRewards ) && 0 < $api_data->honorsRewards->_total )
			$this->honors_and_rewards = $api_data->honorsRewards->values;
		if( ! empty( $api_data->publications ) && 0 < $api_data->publications->_total )
			$this->publications = $api_data->publications->values;
		if( ! empty( $api_data->last_modified ) && "" != $api_data->last_modified )
			$this->last_modified = $api_data->last_modified;
	}
}
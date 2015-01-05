/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */

package com.vejvoda.android.gcm.riprunner.app;

public class FireHallAuthentication {

	String hostURL;
	String firehall_id;
	String user_id;
	String user_password;
	String gcm_registration_id;
	String firehall_geo_lat;
	String firehall_geo_long;
	boolean registered_backend;
	
	public FireHallAuthentication(String hostURL, String firehall_id, 
			String user_id, String user_password, String gcm_registration_id,
				boolean registered_backend) {
		
		this.hostURL = hostURL;
		this.firehall_id = firehall_id;
		this.user_id = user_id;
		this.user_password = user_password;
		this.gcm_registration_id = gcm_registration_id;
		this.registered_backend = registered_backend;
	}
	
	public String getHostURL() {
		return hostURL;
	}
	public String getFirehallId() {
		return firehall_id;
	}
	public String getUserId() {
		return user_id;
	}
	public String getUserPassword() {
		return user_password;
	}
	public String getGCMRegistrationId() {
		return gcm_registration_id;
	}
	public boolean getRegisteredBackend() {
		return registered_backend;
	}
	public void setRegisteredBackend(boolean value) {
		registered_backend = value;
	}
	public void setGCMRegistrationId(String id) {
		gcm_registration_id = id;
	}
	
	public void setFireHallGeoLatitude(String value) {
		firehall_geo_lat = value;
	}
	public String getFireHallGeoLatitude() {
		return firehall_geo_lat;
	}
	public void setFireHallGeoLongitude(String value) {
		firehall_geo_long = value;
	}
	public String getFireHallGeoLongitude() {
		return firehall_geo_long;
	}
	
}

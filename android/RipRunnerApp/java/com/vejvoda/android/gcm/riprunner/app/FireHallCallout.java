/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */

package com.vejvoda.android.gcm.riprunner.app;

public class FireHallCallout {
	String calloutId;
	String calloutKeyId;
	String calloutType;
	String gpsLat;
	String gpsLong;
	String address;
	String mapAddress;
	String units;
	String status;
	
	public FireHallCallout(String calloutId, String calloutKeyId, String calloutType, 
			 String gpsLat, String gpsLong, String address, String mapAddress, 
			 	String units, String status) {
		 this.calloutId = calloutId;
		 this.calloutKeyId = calloutKeyId;
		 this.calloutType = calloutType;
		 this.gpsLat = gpsLat;
		 this.gpsLong = gpsLong;
		 this.address = address;
		 this.mapAddress = mapAddress;
		 this.units = units;
		 this.status = status;
	}
	 
	public String getCalloutId() {
		 return calloutId;
	}
	public String getCalloutKeyId() {
		 return calloutKeyId;
	}
	public String getCalloutType() {
		return calloutType;
	}
	public String getGPSLat() {
		 return gpsLat;
	}
	public String getGPSLong() {
		 return gpsLong;
	}
	public String getAddress() {
		 return address;
	}
	public String getMapAddress() {
		 return mapAddress;
	}
	public String getUnits() {
		 return units;
	}
	public String getStatus() {
		 return status;
	}
	public void setStatus(String value) {
		 this.status = value;
	}
}

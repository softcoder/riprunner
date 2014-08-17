/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */

package com.vejvoda.android.gcm.riprunner.app;

public class FireHallCallout {
	 String calloutId;
	 String gpsLat;
	 String gpsLong;
	 String address;
	 String mapAddress;
	 String units;
	
	 public FireHallCallout(String calloutId, String gpsLat, String gpsLong, String address, String mapAddress, String units) {
		 this.calloutId = calloutId;
		 this.gpsLat = gpsLat;
		 this.gpsLong = gpsLong;
		 this.address = address;
		 this.mapAddress = mapAddress;
		 this.units = units;
	 }
	 
	 public String getCalloutId() {
		 return calloutId;
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
}

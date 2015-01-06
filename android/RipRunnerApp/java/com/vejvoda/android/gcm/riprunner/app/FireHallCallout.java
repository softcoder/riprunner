/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */

package com.vejvoda.android.gcm.riprunner.app;

import java.util.ArrayList;
import java.util.List;

public class FireHallCallout {
	
	private String calloutId;
	private String calloutKeyId;
	private String calloutType;
	private String gpsLat;
	private String gpsLong;
	private String address;
	private String mapAddress;
	private String units;
	private String status;
	private List<Responder> responders;
	
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
	
	public void clearResponders() {
		if(responders != null) {
			responders.clear();
		}
	}
	public void addResponder(Responder responder) {
		if(responders == null) {
			responders = new ArrayList<Responder>();			
		}
		responders.add(responder);
	}

	public List<Responder> getResponders() {
		if(responders == null) {
			responders = new ArrayList<Responder>();			
		}
		return responders;
	}
	
	
	public class Responder {
		
		private String name;
		private String gpsLat;
		private String gpsLong;
		
		public Responder(String name, String gpsLat, String gpsLong) {
			this.name = name;
			this.gpsLat = gpsLat;
			this.gpsLong = gpsLong;
		}
		
		public String getName() {
			return name;
		}
		public String getGPSLat() {
			 return gpsLat;
		}
		public String getGPSLong() {
			 return gpsLong;
		}
	}
}

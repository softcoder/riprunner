/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.gcm.riprunner.app;

import java.io.UnsupportedEncodingException;
import java.net.URLDecoder;

import org.json.JSONException;
import org.json.JSONObject;

import de.quist.app.errorreporter.ExceptionReporter;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.AsyncTask;
import android.util.Log;

public class AppMainBroadcastReceiver extends BroadcastReceiver {
	
	private static Object appMainLock = new Object();
	private static AppMainActivity appMain = null;
	//private AppMainActivity appMain = null;

	public AppMainBroadcastReceiver() {
		super();
		
		//appMain = null;
		Log.i(Utils.TAG, Utils.getLineNumber() + ": RipRunner -> Starting up AppMainBroadcastReceiver.");
	}
	static public void setMainApp(AppMainActivity app) {
		Log.i(Utils.TAG, Utils.getLineNumber() + ": RipRunner -> setMainApp: " + app.toString());
		synchronized(appMainLock) {
			appMain = app;
		}
	}
	private AppMainActivity getMainApp() {
		AppMainActivity result = null;
		synchronized(appMainLock) {
			result = appMain;
		}
		return result;
	}
	
    @Override
    public void onReceive(Context context, Intent intent) {
    	ExceptionReporter.register(context);
    	
    	Log.i(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got intent action: " + (intent == null ? "null" : intent) +
    			" appmain = " + (getMainApp() == null ? "null" : getMainApp().toString()));
    	
        if(intent != null && intent.getAction() != null) {
        	Log.i(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got intent action: " + intent.getAction() +
        			" appmain = " + (getMainApp() == null ? "null" : getMainApp().toString()));
        	
	    	if(intent.getAction().equals(AppMainActivity.RECEIVE_CALLOUT)) {
	    		processRecieveCalloutMsg(intent);
	        }
	        else if(intent.getAction().equals(AppMainActivity.TRACKING_GEO)) {
	        	processTrackingGeoCoordinates();
	        }
	        else {
	        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got ***UNHANDLED*** intent action: " + intent.getAction());
	        }
        }
        else {
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Error null intent or action.");
        }
    }
    
	private void processTrackingGeoCoordinates() {
		Boolean tracking_enabled = getMainApp().isTrackingEnabled();
		if(tracking_enabled != null && tracking_enabled.booleanValue()) {
		    new AsyncTask<Void, Void, String>() {
		    	@Override
		        protected void onPreExecute() {
		            super.onPreExecute();
		    	}
		        @Override
		        protected String doInBackground(Void... params) {
		        	try {
		               	getMainApp().sendGeoTrackingToBackend();
		        	}
		        	catch (Exception e) {
		        		Log.e(Utils.TAG, Utils.getLineNumber() + ": GEO Tracking", e);
						throw new RuntimeException("Error with GEO Tracking: " + e);
		        	}
		        	
		           	return "";
		        }
		        @Override
		        protected void onPostExecute(String msg) {
		        	super.onPostExecute(msg);
		        }
		    }.execute(null, null, null);
		}
	}
    
	private void processRecieveCalloutMsg(Intent intent) {
		String serviceJsonString = "";
		try {
			serviceJsonString = intent.getStringExtra("callout");
			serviceJsonString = FireHallUtil.extractDelimitedValueFromString(
					serviceJsonString, "Bundle\\[(.*?)\\]", 1, true);
			
			JSONObject json = new JSONObject( serviceJsonString );

			if(json.has("DEVICE_MSG")) {
				processDeviceMsgTrigger(json);
			}
			else if(json.has("CALLOUT_MSG")) {
				processCalloutTrigger(json);
			}
			else if(json.has("CALLOUT_RESPONSE_MSG")) {
				processCalloutResponseTrigger(json);       
			}
			else if(json.has("ADMIN_MSG")) {
				processAdminMsgTrigger(json);       
			}
			else {
		    	Log.e(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got UNKNOWN callout message type: " + json.toString());
			}
		}
		catch (JSONException e) {
			Log.e(Utils.TAG, Utils.getLineNumber() + ": " + serviceJsonString, e);
			throw new RuntimeException("Could not parse JSON data: " + e);
		}
		catch (UnsupportedEncodingException e) {
			Log.e(Utils.TAG, Utils.getLineNumber() + ": " + serviceJsonString, e);
			throw new RuntimeException("Could not decode JSON data: " + e);
		}
		catch (Exception e) {
			Log.e(Utils.TAG, Utils.getLineNumber() + ": " + serviceJsonString, e);
			throw new RuntimeException("Error with JSON data: " + e);
		}
	}

	void processAdminMsgTrigger(JSONObject json)
			throws UnsupportedEncodingException, JSONException {
		final String adminMsg = URLDecoder.decode(json.getString("ADMIN_MSG"), "utf-8");
		if(adminMsg != null) {
			getMainApp().processAdminMsgTrigger(adminMsg);
		}
	}
	
	void processDeviceMsgTrigger(JSONObject json)
			throws UnsupportedEncodingException, JSONException {
		final String deviceMsg = URLDecoder.decode(json.getString("DEVICE_MSG"), "utf-8");
		if(deviceMsg != null && deviceMsg.equals("GCM_LOGINOK") == false) {
			getMainApp().processDeviceMsgTrigger(deviceMsg);
		}
	}

	void processCalloutResponseTrigger(JSONObject json)
			throws UnsupportedEncodingException, JSONException {
		final String calloutMsg = URLDecoder.decode(json.getString("CALLOUT_RESPONSE_MSG"), "utf-8");

		String callout_id = URLDecoder.decode(json.getString("call-id"), "utf-8");
		String callout_status = URLDecoder.decode(json.getString("user-status"), "utf-8");
		String response_userid = URLDecoder.decode(json.getString("user-id"), "utf-8");
		
		getMainApp().processCalloutResponseTrigger(calloutMsg, callout_id, 
				callout_status, response_userid);
	}
	

	void processCalloutTrigger(JSONObject json)
			throws UnsupportedEncodingException, JSONException {
		final String calloutMsg = URLDecoder.decode(json.getString("CALLOUT_MSG"), "utf-8");

		String gpsLatStr = "";
		String gpsLongStr = "";
		
		try {
			gpsLatStr = URLDecoder.decode(json.getString("call-gps-lat"), "utf-8");
			gpsLongStr = URLDecoder.decode(json.getString("call-gps-long"), "utf-8");
		}
		catch(Exception e) {
			Log.e(Utils.TAG, Utils.getLineNumber() + ": " + calloutMsg, e);
			
			throw new RuntimeException("Could not parse JSON data: " + e);
		}
		
		String callKeyId = URLDecoder.decode(json.getString("call-key-id"), "utf-8");
		if(callKeyId == null || callKeyId.equals("?")) {
			callKeyId = "";
		}
		String callAddress = URLDecoder.decode(json.getString("call-address"), "utf-8");
		if(callAddress == null || callAddress.equals("?")) {
			callAddress = "";
		}
		String callMapAddress = URLDecoder.decode(json.getString("call-map-address"), "utf-8");
		if(callMapAddress == null || callMapAddress.equals("?")) {
			callMapAddress = "";
		}
		String callType = "?";
		if(json.has("call-type")) {
			callType = URLDecoder.decode(json.getString("call-type"), "utf-8");
		}
				
		getMainApp().processCalloutTrigger(
				URLDecoder.decode(json.getString("call-id"), "utf-8"),
				callKeyId,
				callType,
				gpsLatStr,gpsLongStr,
				callAddress,
				callMapAddress,
				URLDecoder.decode(json.getString("call-units"), "utf-8"),
				URLDecoder.decode(json.getString("call-status"), "utf-8"), 
				calloutMsg);
	}
}

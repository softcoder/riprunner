package com.vejvoda.android.gcm.riprunner.app;

import java.io.UnsupportedEncodingException;
import java.net.URLDecoder;

import org.json.JSONException;
import org.json.JSONObject;

import com.vejvoda.android.gcm.riprunner.app.AppMainActivity.CalloutStatusType;
import com.vejvoda.android.gcm.riprunner.app.AppMainActivity.FireHallSoundPlayer;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.AsyncTask;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;

public class AppMainBroadcastReceiver extends BroadcastReceiver {
	
	private static AppMainActivity appMain = null;
	//private AppMainActivity appMain = null;

	public AppMainBroadcastReceiver() {
		super();
		
		//appMain = null;
		Log.i(Utils.TAG, Utils.getLineNumber() + ": RipRunner -> Starting up AppMainBroadcastReceiver.");
	}
	static public void setMainApp(AppMainActivity app) {
		Log.i(Utils.TAG, Utils.getLineNumber() + ": RipRunner -> setMainApp: " + app.toString());
		appMain = app;
	}
	private AppMainActivity getMainApp() {
		return appMain;
	}
	
    @Override
    public void onReceive(Context context, Intent intent) {
        if(intent != null && intent.getAction() != null) {
        	Log.i(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got intent action: " + intent.getAction() +
        			" appmain = " + (getMainApp() == null ? "null" : getMainApp().toString()));
        	
	    	if(intent.getAction().equals(AppMainActivity.RECEIVE_CALLOUT)) {
	            
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
	        else if(intent.getAction().equals(AppMainActivity.TRACKING_GEO)) {
	        	
	        	Boolean tracking_enabled = getMainApp().getConfigItem(context,AppMainActivity.PROPERTY_TRACKING_ENABLED,Boolean.class);
	        	if(tracking_enabled != null && tracking_enabled.booleanValue()) {
	        		
	                new AsyncTask<Void, Void, String>() {
	                	
	                	@Override
	                    protected void onPreExecute() {
	                        super.onPreExecute();
	                	}
	                	
	                    @Override
	                    protected String doInBackground(Void... params) {
	                    	try {
		                       	String result = getMainApp().sendGeoTrackingToBackend();
		                       	
		                       	if(result != null && result.startsWith("CALLOUT_ENDED=")) {
			                    	processCalloutResponseTrigger("Callout has ended!",
			                    			getMainApp().lastCallout.getCalloutId(), 
			                    			String.valueOf(CalloutStatusType.Complete.valueOf()) );
		                       	}
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
	        else {
	        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got intent action: " + intent.getAction());
	        }
        }
        else {
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Error null intent or action.");
        }
    }

	void processDeviceMsgTrigger(JSONObject json)
			throws UnsupportedEncodingException, JSONException {
		final String deviceMsg = URLDecoder.decode(json.getString("DEVICE_MSG"), "utf-8");
		if(deviceMsg != null && deviceMsg.equals("GCM_LOGINOK") == false) {
			getMainApp().runOnUiThread(new Runnable() {
			    public void run() {
			    	getMainApp().mDisplay = (TextView) getMainApp().findViewById(R.id.display);
			    	getMainApp().mDisplay.append("\n" + deviceMsg);
			   }
			});
		}
	}

	void processCalloutResponseTrigger(JSONObject json)
			throws UnsupportedEncodingException, JSONException {
		final String calloutMsg = URLDecoder.decode(json.getString("CALLOUT_RESPONSE_MSG"), "utf-8");

		String callout_id = URLDecoder.decode(json.getString("call-id"), "utf-8");
		String callout_status = URLDecoder.decode(json.getString("user-status"), "utf-8");
		
		processCalloutResponseTrigger(calloutMsg, callout_id, callout_status);
	}
	void processCalloutResponseTrigger(final String calloutMsg,
			String callout_id, String callout_status) {
		
		if(getMainApp().lastCallout != null) {
			if(getMainApp().lastCallout.getCalloutId().equals(callout_id)) {
				if(getMainApp().lastCallout.getStatus().equals(callout_status) == false) {
					getMainApp().lastCallout.setStatus(callout_status);
				}
			}
		}
		getMainApp().runOnUiThread(new Runnable() {
		    public void run() {
		    	getMainApp().mDisplay = (TextView) getMainApp().findViewById(R.id.display);
		    	getMainApp().mDisplay.append("\n" + calloutMsg);

		    	if(getMainApp().lastCallout != null &&
		    		CalloutStatusType.isComplete(getMainApp().lastCallout.getStatus()) == false) {
		    		
		            Button btnCompleteCall = (Button)getMainApp().findViewById(R.id.btnCompleteCall);
		            btnCompleteCall.setVisibility(View.VISIBLE);
		            btnCompleteCall.setEnabled(true);

		            Button btnCancelCall = (Button)getMainApp().findViewById(R.id.btnCancelCall);
		            btnCancelCall.setVisibility(View.VISIBLE);
		            btnCancelCall.setEnabled(true);
		    	}
		    	else {
		            Button btnCompleteCall = (Button)getMainApp().findViewById(R.id.btnCompleteCall);
		            btnCompleteCall.setVisibility(View.VISIBLE);
		            btnCompleteCall.setEnabled(false);
		            
		            Button btnCancelCall = (Button)getMainApp().findViewById(R.id.btnCancelCall);
		            btnCancelCall.setVisibility(View.VISIBLE);
		            btnCancelCall.setEnabled(false);
		            
			        TextView txtMsg = (TextView)getMainApp().findViewById(R.id.txtMsg);
			        txtMsg.setText(getMainApp().getResources().getString(R.string.waiting_for_callout));
		    	}
		    	
		    	AppMainActivity.playSound(getMainApp().context,FireHallSoundPlayer.SOUND_DINGLING);
		   }
		});
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
		
		getMainApp().lastCallout = new FireHallCallout(
				URLDecoder.decode(json.getString("call-id"), "utf-8"),
				callKeyId,
				gpsLatStr,gpsLongStr,
				callAddress,
				callMapAddress,
				URLDecoder.decode(json.getString("call-units"), "utf-8"),
				URLDecoder.decode(json.getString("call-status"), "utf-8"));
		
		getMainApp().runOnUiThread(new Runnable() {
		    public void run() {

		    	getMainApp().mDisplay = (TextView) getMainApp().findViewById(R.id.display);
		    	getMainApp().mDisplay.setText(calloutMsg);
		    	
		    	AppMainActivity.playSound(getMainApp().context,FireHallSoundPlayer.SOUND_PAGER_TONE_PG);
		    	
		        Button btnMap = (Button)getMainApp().findViewById(R.id.btnMap);
		        btnMap.setVisibility(View.VISIBLE);
		        btnMap.setEnabled(true);
		        
		        Button btnRespond = (Button)getMainApp().findViewById(R.id.btnRespond);
		        btnRespond.setVisibility(View.VISIBLE);
		        btnRespond.setEnabled(true);
		        
		    	if(CalloutStatusType.isComplete(getMainApp().lastCallout.getStatus()) == false) {
	                Button btnCompleteCall = (Button)getMainApp().findViewById(R.id.btnCompleteCall);
	                btnCompleteCall.setVisibility(View.VISIBLE);
	                btnCompleteCall.setEnabled(true);
	                
		            Button btnCancelCall = (Button)getMainApp().findViewById(R.id.btnCancelCall);
		            btnCancelCall.setVisibility(View.VISIBLE);
		            btnCancelCall.setEnabled(true);
		    	}
		    	else {
		            Button btnCompleteCall = (Button)getMainApp().findViewById(R.id.btnCompleteCall);
		            btnCompleteCall.setVisibility(View.VISIBLE);
		            btnCompleteCall.setEnabled(false);
		            
		            Button btnCancelCall = (Button)getMainApp().findViewById(R.id.btnCancelCall);
		            btnCancelCall.setVisibility(View.VISIBLE);
		            btnCancelCall.setEnabled(false);
		    	}
		   }
		});
	}
}

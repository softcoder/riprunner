/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.riprunner;

import java.io.BufferedInputStream;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.Map;

import android.content.IntentFilter;

public class Utils {

    /**
     * Tag used on log messages.
     */
    static final String TAG = "RipRunner";
    static final String APK_NAME = "RipRunnerApp.apk";
	
    private static IntentFilter filter = null;
	private static IntentFilter filterBroadcastReceiver = null;
    
    // Your activity will respond to this action String
	public static final String REGISTRATION_COMPLETE = "registrationComplete";
	public static final String REGISTRATION_COMPLETE_MAIN = "registrationComplete_main";

    public static final String RECEIVE_CALLOUT = "callout_data";
    public static final String TRACKING_GEO = "tracking_data";

    public static final String RECEIVE_CALLOUT_MAIN = "callout_data_main";
    public static final String TRACKING_GEO_MAIN = "tracking_data_main";
    
	public static int getLineNumber() {
		if(Thread.currentThread().getStackTrace().length > 2) {
			return Thread.currentThread().getStackTrace()[2].getLineNumber();
		}
		return -1;
	}	
	public static IntentFilter getMainAppIntentFilter() {
		if(filter == null) {
			filter = new IntentFilter();
			filter.addAction(REGISTRATION_COMPLETE_MAIN);
			filter.addAction(RECEIVE_CALLOUT_MAIN);
			filter.addAction(TRACKING_GEO_MAIN);
		}
		return filter;
	}

	public static IntentFilter getBroadCastReceiverIntentFilter() {
		if(filterBroadcastReceiver == null) {
			filterBroadcastReceiver= new IntentFilter();
			filterBroadcastReceiver.addAction(REGISTRATION_COMPLETE);
			filterBroadcastReceiver.addAction(RECEIVE_CALLOUT);
			filterBroadcastReceiver.addAction(TRACKING_GEO);
		}
		return filterBroadcastReceiver;
	}

	public static String getURLParamString(Map<String, String> params) throws UnsupportedEncodingException {
	    StringBuilder result = new StringBuilder();
	    boolean first = true;
	    for(Map.Entry<String, String> entry : params.entrySet()){
	        if (first)
	            first = false;
	        else
	            result.append("&");
	
	        result.append(URLEncoder.encode(entry.getKey(), "UTF-8"));
	        result.append("=");
	        result.append(URLEncoder.encode(entry.getValue(), "UTF-8"));
	    }
	
	    return result.toString();
	}
	public static HttpURLConnection openHttpConnection(String URL, String requestMethod) throws IOException {
        URL url = new URL(URL);
        HttpURLConnection urlConnection = (HttpURLConnection) url.openConnection();
        urlConnection.setRequestMethod(requestMethod);
        urlConnection.connect();
		return urlConnection;
	}
	public static String getUrlConnectionResultSring(HttpURLConnection urlConnection) throws IOException {
        BufferedInputStream in = new BufferedInputStream(urlConnection.getInputStream());
        ByteArrayOutputStream byteArrayOut = new ByteArrayOutputStream();
        int c;
        while ((c = in.read()) != -1) {
          byteArrayOut.write(c);
        }
		return new String(byteArrayOut.toByteArray());
	}
	public static String getUrlConnectionErorResultSring(HttpURLConnection urlConnection) throws IOException {
        BufferedInputStream in = new BufferedInputStream(urlConnection.getErrorStream());
        ByteArrayOutputStream byteArrayOut = new ByteArrayOutputStream();
        int c;
        while ((c = in.read()) != -1) {
          byteArrayOut.write(c);
        }            
        return new String(byteArrayOut.toByteArray());
	}

}

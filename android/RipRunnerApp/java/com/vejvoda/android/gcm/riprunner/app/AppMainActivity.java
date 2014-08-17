/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.gcm.riprunner.app;

import com.vejvoda.android.gcm.riprunner.app.R;
import com.google.android.gms.common.ConnectionResult;
import com.google.android.gms.common.GooglePlayServicesUtil;
import com.google.android.gms.gcm.GoogleCloudMessaging;

import android.app.ProgressDialog;
import android.content.ActivityNotFoundException;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager.NameNotFoundException;
import android.net.Uri;
import android.os.AsyncTask;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.support.v4.content.LocalBroadcastManager;
import android.support.v7.app.ActionBarActivity;
import android.util.Log;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.View.OnFocusChangeListener;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.net.URLDecoder;
import java.net.URLEncoder;
import java.util.HashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.concurrent.atomic.AtomicInteger;

import org.apache.http.HttpResponse;
import org.apache.http.HttpStatus;
import org.apache.http.NameValuePair;
import org.apache.http.StatusLine;
import org.apache.http.client.ClientProtocolException;
import org.apache.http.client.HttpClient;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.client.utils.URLEncodedUtils;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;
import org.json.JSONException;
import org.json.JSONObject;

import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.media.AudioManager;
import android.media.SoundPool;

/**
 * Main UI for the demo app.
 */
public class AppMainActivity extends ActionBarActivity implements LocationListener {

    public static final String PROPERTY_REG_ID = "registration_id";
        
    public static final String PROPERTY_WEBSITE_URL = "host_url";
    public static final String PROPERTY_SENDER_ID 	= "sender_id";
    
    public static final String PROPERTY_FIREHALL_ID = "firehall_id";
    public static final String PROPERTY_USER_ID = "user_id";
    
    private static final String PROPERTY_APP_VERSION = "appVersion";
    private static final int PLAY_SERVICES_RESOLUTION_REQUEST = 9000;

    /**
     * Substitute you own sender ID here. This is the project number you got
     * from the API Console of Google GCM, as described in "Getting Started."
     */
    // This values enter and read from Settings (preferences screen)
    //String SENDER_ID = "77585175019";
    //String SERVER_BASE_URL = "http://www.soft-haus.com/svvfd/riprunner/";

    /**
     * Tag used on log messages.
     */
    static final String TAG = "Rip Runner";

    TextView mDisplay;
    GoogleCloudMessaging gcm;
    AtomicInteger msgId = new AtomicInteger();
    Context context;

    static final int SETTINGS_RESULT = 1;
    ProgressDialog loadingDlg = null;
    		
	public class FireHallSoundPlayer {
	     public static final int SOUND_DINGLING = R.raw.dingling;
	     public static final int SOUND_LOGIN = R.raw.login;
	     public static final int SOUND_PAGE1 = R.raw.page1;
	     public static final int SOUND_PAGER_TONE_PG = R.raw.pager_tone_pg;
    }
	
    private static SoundPool soundPool;
    private static Map<Integer,Integer> soundPoolMap;

    FireHallAuthentication auth;
    FireHallCallout lastCallout;
    
    //Your activity will respond to this action String
    public static final String RECEIVE_CALLOUT = "callout_data";

    private BroadcastReceiver bReceiver = null;
    
    // flag for GPS status
    boolean isGPSEnabled = false;
 
    // flag for network status
    boolean isNetworkEnabled = false;
 
    boolean canGetLocation = false;    
    Location location; // location
    double latitude; // latitude
    double longitude; // longitude
 
    // The minimum distance to change Updates in meters
    private static final long MIN_DISTANCE_CHANGE_FOR_UPDATES = 10; // 10 meters
 
    // The minimum time between updates in milliseconds
    private static final long MIN_TIME_BW_UPDATES = 1000 * 60 * 1; // 1 minute
 
    // Declaring a Location Manager
    protected LocationManager locationManager;
    
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        setContentView(R.layout.main);
        mDisplay = (TextView) findViewById(R.id.display);

        context = getApplicationContext();

        initSounds(context);

        LocalBroadcastManager bManager = LocalBroadcastManager.getInstance(this);
        IntentFilter intentFilter = new IntentFilter();
        intentFilter.addAction(RECEIVE_CALLOUT);
        bManager.registerReceiver(getBroadCastReceiver(), intentFilter);        
            
        getProgressDialog();
        
    	final EditText etFhid = (EditText)findViewById(R.id.etFhid);
    	final EditText etUid = (EditText)findViewById(R.id.etUid);
    	EditText etUpw = (EditText)findViewById(R.id.etUpw);
    	
    	etFhid.setSelectAllOnFocus(true);
    	etUid.setSelectAllOnFocus(true);
        setupLoginUI();
        
        // Check device for Play Services APK. If check succeeds, proceed with GCM registration.
        if (checkPlayServices()) {
            
        	if(hasConfigItem(context,PROPERTY_WEBSITE_URL) && hasConfigItem(context,PROPERTY_SENDER_ID)) {
        		
        		//etHostUrl.setText(getConfigItem(context,PROPERTY_WEBSITE_URL));
	            etFhid.setText(getConfigItem(context,PROPERTY_FIREHALL_ID));
	            etUid.setText(getConfigItem(context,PROPERTY_USER_ID));
        	}
        	else {
        		openSettings();
        	}
	        
	        etUpw.setText("");
        } 
        else {
            Log.i(TAG, "No valid Google Play Services APK found.");
        }
        
        etUid.requestFocus();
        etFhid.requestFocus();
    }

	void setupOnFocusListeners() {
		
    	final EditText etFhid = (EditText)findViewById(R.id.etFhid);
    	final EditText etUid = (EditText)findViewById(R.id.etUid);
		    	
		etFhid.setOnFocusChangeListener(new OnFocusChangeListener() {
            public void onFocusChange(View arg0, boolean arg1) {
            	final EditText etFhid = (EditText)findViewById(R.id.etFhid);
            	setEditSelectTextIfRequired(etFhid,getResources().getString(R.string.firehallid));
            }
        });
        
        etUid.setOnFocusChangeListener(new OnFocusChangeListener() {
            public void onFocusChange(View arg0, boolean arg1) {
            	final EditText etUid = (EditText)findViewById(R.id.etUid);
            	setEditSelectTextIfRequired(etUid,getResources().getString(R.string.userid));
            }
        });
	}

    private void setEditSelectTextIfRequired(final EditText etCtl, String defaultStr) {
    	if(etCtl.getText().toString().equals(defaultStr)) {
    		etCtl.selectAll();
    	}    	
    }
    
    private void setupLoginUI() {
        auth = null;
        lastCallout = null;
        
// http://developer.android.com/google/gcm/adv.html#unreg-why        
//        if(gcm != null) {
//        	try {
//				gcm.unregister();
//			} 
//        	catch (IOException e) {
//				// TODO Auto-generated catch block
//				//e.printStackTrace();
//				Log.i(TAG, "GCM could not unregister: " + e.getMessage());
//			}
//        }
//        gcm = null;

        mDisplay.setText("");
        
        TextView txtMsg = (TextView)findViewById(R.id.txtMsg);
        txtMsg.setText(getResources().getString(R.string.login_credentials));
    	
        Button btnLogin = (Button)findViewById(R.id.btnLogin);
        btnLogin.setText(getResources().getString(R.string.login));
        Button btnMap = (Button)findViewById(R.id.btnMap);
        btnMap.setEnabled(false);
        Button btnRespond = (Button)findViewById(R.id.btnRespond);
        btnRespond.setEnabled(false);
        
        EditText etFhid = (EditText)findViewById(R.id.etFhid);
        etFhid.setText(getResources().getString(R.string.firehallid));
        etFhid.setVisibility(View.VISIBLE);
        EditText etUid = (EditText)findViewById(R.id.etUid);
        etUid.setText(getResources().getString(R.string.userid));
        etUid.setVisibility(View.VISIBLE);
        EditText etUpw = (EditText)findViewById(R.id.etUpw);
        etUpw.setText("");
        etUpw.setVisibility(View.VISIBLE);
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        // Check device for Play Services APK.
        checkPlayServices();
    }

    /**
     * Check the device to make sure it has the Google Play Services APK. If
     * it doesn't, display a dialog that allows users to download the APK from
     * the Google Play Store or enable it in the device's system settings.
     */
    private boolean checkPlayServices() {
        int resultCode = GooglePlayServicesUtil.isGooglePlayServicesAvailable(this);
        if (resultCode != ConnectionResult.SUCCESS) {
            if (GooglePlayServicesUtil.isUserRecoverableError(resultCode)) {
                GooglePlayServicesUtil.getErrorDialog(resultCode, this,
                        PLAY_SERVICES_RESOLUTION_REQUEST).show();
            } 
            else {
                Log.i(TAG, "This device is not supported.");
                finish();
            }
            return false;
        }
        return true;
    }

    /**
     * Stores the registration ID and the app versionCode in the application's
     * {@code SharedPreferences}.
     *
     * @param context application's context.
     * @param regId registration ID
     */
    private void storeConfigItem(Context context, String keyName, String value) {
        final SharedPreferences prefs = getGcmPreferences(context);
        int appVersion = getAppVersion(context);
        Log.i(TAG, "Saving " + keyName + " on app version " + appVersion);
        SharedPreferences.Editor editor = prefs.edit();
        editor.putString(keyName, value);
        editor.putInt(PROPERTY_APP_VERSION, appVersion);
        editor.commit();
    }

    private boolean hasConfigItem(Context context, String keyName) {
        final SharedPreferences prefs = getGcmPreferences(context);
        String value = prefs.getString(keyName, "");
        if (value == null || value.isEmpty()) {
            return false;
        }
        // Check if app was updated; if so, it must clear the registration ID
        // since the existing regID is not guaranteed to work with the new
        // app version.
        int registeredVersion = prefs.getInt(PROPERTY_APP_VERSION, Integer.MIN_VALUE);
        int currentVersion = getAppVersion(context);
        if (registeredVersion != currentVersion) {
            return false;
        }
        return true;
    }
    
    /**
     * Gets the current registration ID for application on GCM service, if there is one.
     * <p>
     * If result is empty, the app needs to register.
     *
     * @return registration ID, or empty string if there is no existing
     *         registration ID.
     */
    private String getConfigItem(Context context, String keyName) {
        final SharedPreferences prefs = getGcmPreferences(context);
        String value = prefs.getString(keyName, "");
        if (value == null || value.isEmpty()) {
            Log.i(TAG, "Config Item not found: " + keyName);
            return "";
        }
        // Check if app was updated; if so, it must clear the registration ID
        // since the existing regID is not guaranteed to work with the new
        // app version.
        int registeredVersion = prefs.getInt(PROPERTY_APP_VERSION, Integer.MIN_VALUE);
        int currentVersion = getAppVersion(context);
        if (registeredVersion != currentVersion) {
            Log.i(TAG, "App version changed.");
            return "";
        }
        return value;
    }

    /**
     * Registers the application with GCM servers asynchronously.
     * <p>
     * Stores the registration ID and the app versionCode in the application's
     * shared preferences.
     */
    private void registerInBackground() {
        new AsyncTask<Void, Void, String>() {
        	
        	@Override
            protected void onPreExecute() {
                super.onPreExecute();
                
                runOnUiThread(new Runnable() {
                    public void run() {
                
                    	showProgressDialog(true, "Loading...");
                    }
                });
        	}
            @Override
            protected String doInBackground(Void... params) {
                String msg = "";
                try {
                    if (gcm == null) {
                        gcm = GoogleCloudMessaging.getInstance(context);
                    }
                    
                    String regid = getConfigItem(context,PROPERTY_REG_ID);
                    if (regid.isEmpty()) {
                    	regid = gcm.register(getConfigItem(context,PROPERTY_SENDER_ID));

                    	// Persist the regID - no need to register again.
                    	storeConfigItem(context, PROPERTY_REG_ID, regid);
                    }
                    
                    EditText etFhid = (EditText)findViewById(R.id.etFhid);
                    EditText etUid = (EditText)findViewById(R.id.etUid);
                    EditText etUpw = (EditText)findViewById(R.id.etUpw);
                    
                    auth = new FireHallAuthentication(
                    		getConfigItem(context,PROPERTY_WEBSITE_URL).toString(), 
                    		etFhid.getText().toString(),
                    		etUid.getText().toString(), etUpw.getText().toString(),
                    		regid, false);
                    //msg = "Device registered, ID:\n[" + regid + "]";
                    msg = getResources().getString(R.string.waiting_for_callout);
                    
                    // You should send the registration ID to your server over HTTP, so it
                    // can use GCM/HTTP or CCS to send messages to your app.
                    sendRegistrationIdToBackend(auth);
                } 
                catch (IOException ex) {
                    msg = "Error :" + ex.getMessage();
                    // If there is an error, don't just keep trying to register.
                    // Require the user to click a button again, or perform
                    // exponential back-off.
                }
                return msg;
            }

            @Override
            protected void onPostExecute(String msg) {
                mDisplay.append(msg + "\n");
            }
        }.execute(null, null, null);
    }

    /**
     * Registers the application with GCM servers asynchronously.
     * <p>
     * Stores the registration ID and the app versionCode in the application's
     * shared preferences.
     */
    private void respondInBackground() {
        new AsyncTask<Void, Void, String>() {
        	
        	@Override
            protected void onPreExecute() {
                super.onPreExecute();
                
                runOnUiThread(new Runnable() {
                    public void run() {
                    	showProgressDialog(true, "Loading...");
                    }
                });
        	}
            @Override
            protected String doInBackground(Void... params) {
                String msg = "";
                try {
                    // You should send the registration ID to your server over HTTP, so it
                    // can use GCM/HTTP or CCS to send messages to your app.
                    sendResponseToBackend(auth);
                } 
                catch (IOException ex) {
                    msg = "Error :" + ex.getMessage();
                    // If there is an error, don't just keep trying to register.
                    // Require the user to click a button again, or perform
                    // exponential back-off.
                }
                return msg;
            }

            @Override
            protected void onPostExecute(String msg) {
                mDisplay.append(msg + "\n");
            }
        }.execute(null, null, null);
    }
    
    // Send an upstream message.
    public void onClick(final View view) {

        if (view == findViewById(R.id.btnLogin)) {
            new AsyncTask<Void, Void, String>() {
                @Override
                protected String doInBackground(Void... params) {
                	
                    // Check device for Play Services APK. If check succeeds, proceed with GCM registration.
                    if (checkPlayServices()) {
                    	if(auth == null) {
                    		registerInBackground();
                    	}
                    	else {
                    		stopGPSTracking();
                    		
        		            runOnUiThread(new Runnable() {
        		                public void run() {
        		                	setupLoginUI();
        		                	playSound(context,FireHallSoundPlayer.SOUND_DINGLING);
        		               }
        		            });       
                    	}
                    } 
                    else {
                        Log.i(TAG, "No valid Google Play Services APK found.");
                    }
                	return "";
                }

                @Override
                protected void onPostExecute(String msg) {
                    mDisplay.append(msg + "\n");
                }
            }.execute(null, null, null);
        }
        else if (view == findViewById(R.id.btnMap)) {
			try {
				String uri = String.format(Locale.ENGLISH, "geo:%s,%s?q=%s", 
						URLEncoder.encode(lastCallout.getGPSLat(), "utf-8"), 
						URLEncoder.encode(lastCallout.getGPSLong(), "utf-8"),
						URLEncoder.encode(lastCallout.getMapAddress(), "utf-8"));
				
	        	Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
	        	intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
	        	if (intent.resolveActivity(getPackageManager()) != null) {
	        		context.startActivity(intent);
	        	}
	        	else {
	        		uri = String.format(Locale.ENGLISH, "http://maps.google.com/maps?&daddr=%s,%s (%s)", 
	        				URLEncoder.encode(lastCallout.getGPSLat(), "utf-8"), 
	        				URLEncoder.encode(lastCallout.getGPSLong(), "utf-8"), 
	        				URLEncoder.encode(lastCallout.getMapAddress(), "utf-8"));
	                intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
	                intent.setClassName("com.google.android.apps.maps", "com.google.android.maps.MapsActivity");
	                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
	                try {
	                	context.startActivity(intent);
	                }
	                catch(ActivityNotFoundException innerEx) {
	                    Toast.makeText(this, "Please install a maps application", Toast.LENGTH_LONG).show();
	                }
	        	}
			} 
			catch (UnsupportedEncodingException e) {
				// TODO Auto-generated catch block
				//e.printStackTrace();
				Toast.makeText(this, "UnsupportedEncodingException: " + e.getMessage(), Toast.LENGTH_LONG).show();
			}
        }
        else if (view == findViewById(R.id.btnRespond)) {
            new AsyncTask<Void, Void, String>() {
                @Override
                protected String doInBackground(Void... params) {

                    // Check device for Play Services APK. If check succeeds, proceed with GCM registration.
                    if (checkPlayServices()) {
                    	if(auth != null) {
                    		respondInBackground();
                    	}
                    } 
                    else {
                        Log.i(TAG, "No valid Google Play Services APK found.");
                    }
                	return "";
                }

                @Override
                protected void onPostExecute(String msg) {
                    mDisplay.append(msg + "\n");
                }
            }.execute(null, null, null);
        }
        else if (view == findViewById(R.id.btnClear)) {
            mDisplay.setText("");
            stopGPSTracking();
        }
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        
        stopGPSTracking();
    }

    /**
     * @return Application's version code from the {@code PackageManager}.
     */
    private static int getAppVersion(Context context) {
        try {
            PackageInfo packageInfo = context.getPackageManager()
                    .getPackageInfo(context.getPackageName(), 0);
            return packageInfo.versionCode;
        } 
        catch (NameNotFoundException e) {
            // should never happen
            throw new RuntimeException("Could not get package name: " + e);
        }
    }

    /**
     * @return Application's {@code SharedPreferences}.
     */
    private SharedPreferences getGcmPreferences(Context context) {
        // This sample app persists the registration ID in shared preferences, but
        // how you store the regID in your app is up to you.
        return getSharedPreferences(AppMainActivity.class.getSimpleName(),
                Context.MODE_PRIVATE);
    }
    
    /**
     * Sends the registration ID to your server over HTTP, so it can use GCM/HTTP or CCS to send
     * messages to your app. Not needed for this demo since the device sends upstream messages
     * to a server that echoes back the message using the 'from' address in the message.
     * @throws IOException 
     * @throws ClientProtocolException 
     */
    private void sendRegistrationIdToBackend(FireHallAuthentication auth) throws ClientProtocolException, IOException {
    	
    	List<NameValuePair> params = new LinkedList<NameValuePair>();
    	params.add(new BasicNameValuePair("rid", auth.getGCMRegistrationId()));
    	params.add(new BasicNameValuePair("fhid", auth.getFirehallId()));
    	params.add(new BasicNameValuePair("uid", auth.getUserId()));
    	params.add(new BasicNameValuePair("upwd", auth.getUserPassword()));
    	String paramString = URLEncodedUtils.format(params, "utf-8");
    	String URL = auth.getHostURL() + "register_device.php?" + paramString;
    	
    	HttpClient httpclient = new DefaultHttpClient();
        HttpResponse response = httpclient.execute(new HttpGet(URL));
        StatusLine statusLine = response.getStatusLine();
        if(statusLine.getStatusCode() == HttpStatus.SC_OK){
            ByteArrayOutputStream out = new ByteArrayOutputStream();
            response.getEntity().writeTo(out);
            out.close();
            
            final String responseString = out.toString();
            if(responseString != null && responseString.startsWith("OK=")) {
	            storeConfigItem(context, PROPERTY_USER_ID, auth.getUserId());
	            
	            runOnUiThread(new Runnable() {
	                public void run() {
	                	
	                    Button btnLogin = (Button)findViewById(R.id.btnLogin);
	                    btnLogin.setText(getResources().getString(R.string.logout));
	                	
	                    TextView txtMsg = (TextView)findViewById(R.id.txtMsg);
	                    txtMsg.setText(getResources().getString(R.string.login_success));
	                    
	                    EditText etFhid = (EditText)findViewById(R.id.etFhid);
	                    etFhid.setText("");
	                    etFhid.setVisibility(View.GONE);
	                    EditText etUid = (EditText)findViewById(R.id.etUid);
	                    etUid.setText("");
	                    etUid.setVisibility(View.GONE);
	                    EditText etUpw = (EditText)findViewById(R.id.etUpw);
	                    etUpw.setText("");
	                    etUpw.setVisibility(View.GONE);
	                    
	                    playSound(context,FireHallSoundPlayer.SOUND_LOGIN);
	                    
	                    showProgressDialog(false, null);
	               }
	            });
            }
            else {
                runOnUiThread(new Runnable() {
                    public void run() {
                        EditText etUpw = (EditText)findViewById(R.id.etUpw);
                        etUpw.setText("");
                        
                        TextView txtMsg = (TextView)findViewById(R.id.txtMsg);
                        txtMsg.setText("Invalid login attempt: " + responseString);
                        
                        showProgressDialog(false, null);
                   }
                });            
                
                //throw new IOException("Error during registration: " + statusLine.getReasonPhrase());
            }
        } 
        else {
            //Closes the connection.
            response.getEntity().getContent().close();

            final String errorText = statusLine.getReasonPhrase();
            runOnUiThread(new Runnable() {
                public void run() {
                    EditText etUpw = (EditText)findViewById(R.id.etUpw);
                    etUpw.setText("");
                    
                    TextView txtMsg = (TextView)findViewById(R.id.txtMsg);
                    txtMsg.setText("Error during login: " + errorText);
                    
                    showProgressDialog(false, null);
               }
            });            
            
            //throw new IOException("Error during registration: " + statusLine.getReasonPhrase());
        }    	
    }

    /**
     * Sends the registration ID to your server over HTTP, so it can use GCM/HTTP or CCS to send
     * messages to your app. Not needed for this demo since the device sends upstream messages
     * to a server that echoes back the message using the 'from' address in the message.
     * @throws IOException 
     * @throws ClientProtocolException 
     */
    private void sendResponseToBackend(FireHallAuthentication auth) throws ClientProtocolException, IOException {
    	
    	List<NameValuePair> params = new LinkedList<NameValuePair>();
    	params.add(new BasicNameValuePair("cid", lastCallout.getCalloutId()));
    	params.add(new BasicNameValuePair("fhid", auth.getFirehallId()));
    	params.add(new BasicNameValuePair("uid", auth.getUserId()));
    	params.add(new BasicNameValuePair("upwd", auth.getUserPassword()));
    	params.add(new BasicNameValuePair("lat", String.valueOf(getLatitude())));
    	params.add(new BasicNameValuePair("long", String.valueOf(getLongitude())));
    	params.add(new BasicNameValuePair("status", "1"));
    	
    	String paramString = URLEncodedUtils.format(params, "utf-8");
    	String URL = auth.getHostURL() + "cr.php?" + paramString;
    	
    	HttpClient httpclient = new DefaultHttpClient();
        HttpResponse response = httpclient.execute(new HttpGet(URL));
        StatusLine statusLine = response.getStatusLine();
        if(statusLine.getStatusCode() == HttpStatus.SC_OK){
            ByteArrayOutputStream out = new ByteArrayOutputStream();
            response.getEntity().writeTo(out);
            out.close();
            
            final String responseString = out.toString();
            if(responseString != null && responseString.startsWith("OK=")) {
	            
	            runOnUiThread(new Runnable() {
	                public void run() {
	                	
	                    Button btnRespond = (Button)findViewById(R.id.btnRespond);
	                    btnRespond.setEnabled(false);
	                	
	                    TextView txtMsg = (TextView)findViewById(R.id.txtMsg);
	                    txtMsg.setText(getResources().getString(R.string.callout_respond_success));
	                    
	                    playSound(context,FireHallSoundPlayer.SOUND_DINGLING);
	                    
	                    showProgressDialog(false, null);
	               }
	            });
            }
            else {
                runOnUiThread(new Runnable() {
                    public void run() {
                        
                        TextView txtMsg = (TextView)findViewById(R.id.txtMsg);
                        txtMsg.setText("Invalid server response: " + responseString);
                        
                        showProgressDialog(false, null);
                   }
                });            
                
                //throw new IOException("Error during registration: " + statusLine.getReasonPhrase());
            }
        } 
        else {
            //Closes the connection.
            response.getEntity().getContent().close();

            final String errorText = statusLine.getReasonPhrase();
            runOnUiThread(new Runnable() {
                public void run() {
                    
                    TextView txtMsg = (TextView)findViewById(R.id.txtMsg);
                    txtMsg.setText("Error during server response: " + errorText);
                    
                    showProgressDialog(false, null);
               }
            });            
            
            //throw new IOException("Error during registration: " + statusLine.getReasonPhrase());
        }    	
    }
    
    ProgressDialog getProgressDialog() {
    	if(loadingDlg == null) {
    		loadingDlg = new ProgressDialog(AppMainActivity.this);	
    	}
    	return loadingDlg;
    }
    void showProgressDialog(boolean show, String msg) {
    	if(show) {
    		getProgressDialog().setMessage(msg);
    		getProgressDialog().setProgressStyle(ProgressDialog.STYLE_SPINNER);
    		getProgressDialog().setIndeterminate(true);
    		getProgressDialog().setCancelable(false);
    		getProgressDialog().show();
    	}
    	else {
    		getProgressDialog().hide();
    	}
    }

    public void stopGPSTracking() {
    	if(locationManager != null) {
    		locationManager.removeUpdates(AppMainActivity.this); 
    		locationManager = null;
    	}
    }
    
    public Location getLocation() {
        try {
        	if(locationManager == null) {
	            locationManager = (LocationManager) context
	                    .getSystemService(LOCATION_SERVICE);
        	}
 
            // getting GPS status
            isGPSEnabled = locationManager
                    .isProviderEnabled(LocationManager.GPS_PROVIDER);
 
            // getting network status
            isNetworkEnabled = locationManager
                    .isProviderEnabled(LocationManager.NETWORK_PROVIDER);
 
            if (!isGPSEnabled && !isNetworkEnabled) {
                // no network provider is enabled
            } 
            else {
            	
                this.canGetLocation = true;
                
                // First get location from Network Provider
                if (isNetworkEnabled) {
                    locationManager.requestLocationUpdates(
                            LocationManager.NETWORK_PROVIDER,
                            MIN_TIME_BW_UPDATES,
                            MIN_DISTANCE_CHANGE_FOR_UPDATES, this);
                    
                    Log.d("Network", "Network");
                    
                    if (locationManager != null) {
                        location = locationManager
                                .getLastKnownLocation(LocationManager.NETWORK_PROVIDER);
                        if (location != null) {
                            latitude = location.getLatitude();
                            longitude = location.getLongitude();
                        }
                    }
                }
                
                // if GPS Enabled get lat/long using GPS Services
                if (isGPSEnabled) {
                    if (location == null) {
                        locationManager.requestLocationUpdates(
                                LocationManager.GPS_PROVIDER,
                                MIN_TIME_BW_UPDATES,
                                MIN_DISTANCE_CHANGE_FOR_UPDATES, this);
                        
                        Log.d("GPS Enabled", "GPS Enabled");
                        
                        if (locationManager != null) {
                            location = locationManager
                                    .getLastKnownLocation(LocationManager.GPS_PROVIDER);
                            if (location != null) {
                                latitude = location.getLatitude();
                                longitude = location.getLongitude();
                            }
                        }
                    }
                }
            }
        } 
        catch (Exception e) {
            e.printStackTrace();
        }
 
        return location;
    }
    
    /**
     * Function to get latitude
     * */
    public double getLatitude() {
        if(location != null){
            latitude = location.getLatitude();
        }
         
        // return latitude
        return latitude;
    }
     
    /**
     * Function to get longitude
     * */
    public double getLongitude() {
        if(location != null){
            longitude = location.getLongitude();
        }
         
        // return longitude
        return longitude;
    }
    
    /**
     * Function to check if best network provider
     * @return boolean
     * */
    public boolean canGetLocation() {
        return this.canGetLocation;
    }
    
	@Override
	public void onLocationChanged(Location location) {
	}

	@Override
	public void onStatusChanged(String provider, int status, Bundle extras) {
	}

	@Override
	public void onProviderEnabled(String provider) {
	}

	@Override
	public void onProviderDisabled(String provider) {
	}
	
	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
	    // Inflate the menu items for use in the action bar
	    MenuInflater inflater = getMenuInflater();
	    inflater.inflate(R.menu.main_activity_actions, menu);
	    return super.onCreateOptionsMenu(menu);
	}
	
	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
	    // Handle presses on the action bar items
	    switch (item.getItemId()) {
	        case R.id.action_settings:
	            openSettings();
	            return true;
	        default:
	            return super.onOptionsItemSelected(item);
	    }
	}
	
	private void openSettings() {
    	  Intent intent = new Intent(getApplicationContext(), SettingsActivity.class);
    	  intent.setClass(AppMainActivity.this, SettingsActivity.class);
    	  startActivityForResult(intent, 0); 
	}
	
	@Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
	    super.onActivityResult(requestCode, resultCode, data);
	
        displayUserSettings();
    }
    
    private void displayUserSettings() {
	    SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(this);
	
	    String host_url = sharedPrefs.getString("host_url", "http://www.soft-haus.com/svvfd/riprunner/");
	    String sender_id = sharedPrefs.getString("sender_id", "77585175019");
	
	    storeConfigItem(context, PROPERTY_WEBSITE_URL, host_url);
	    storeConfigItem(context, PROPERTY_SENDER_ID, sender_id);
    }

    private BroadcastReceiver getBroadCastReceiver() {
    	if(bReceiver == null) {
	    	bReceiver = new BroadcastReceiver() {
		        @Override
		        public void onReceive(Context context, Intent intent) {
		            if(intent.getAction().equals(RECEIVE_CALLOUT)) {
		                
		            	String serviceJsonString = intent.getStringExtra("callout");
		            	serviceJsonString = FireHallUtil.extractDelimitedValueFromString(
		            			serviceJsonString, "Bundle\\[(.*?)\\]", 1, true);
		            	try {
							JSONObject json = new JSONObject( serviceJsonString );
							
							if(json.has("CALLOUT_MSG")) {
								String calloutMsg = URLDecoder.decode(json.getString("CALLOUT_MSG"), "utf-8");
								
				            	mDisplay = (TextView) findViewById(R.id.display);
				            	mDisplay.setText(calloutMsg);
			
				            	String gpsLatStr = URLDecoder.decode(json.getString("call-gps-lat"), "utf-8");
				            	String gpsLongStr = URLDecoder.decode(json.getString("call-gps-long"), "utf-8");
				            	
								lastCallout = new FireHallCallout(
										URLDecoder.decode(json.getString("call-id"), "utf-8"),
										gpsLatStr,gpsLongStr,
										URLDecoder.decode(json.getString("call-address"), "utf-8"),
										URLDecoder.decode(json.getString("call-map-address"), "utf-8"),
										URLDecoder.decode(json.getString("call-units"), "utf-8"));
			
				            	playSound(context,FireHallSoundPlayer.SOUND_PAGER_TONE_PG);
				            	
				            	getLocation();
				            	
				                runOnUiThread(new Runnable() {
				                    public void run() {
				                        Button btnMap = (Button)findViewById(R.id.btnMap);
				                        btnMap.setEnabled(true);
				                        
				                        Button btnRespond = (Button)findViewById(R.id.btnRespond);
				                        btnRespond.setEnabled(true);
				                   }
				                });
							}
							else if(json.has("CALLOUT_RESPONSE_MSG")) {
								String calloutMsg = URLDecoder.decode(json.getString("CALLOUT_RESPONSE_MSG"), "utf-8");
		
				            	mDisplay = (TextView) findViewById(R.id.display);
				            	mDisplay.append(calloutMsg);
			
				            	playSound(context,FireHallSoundPlayer.SOUND_DINGLING);
							}
						}
		            	catch (JSONException e) {
							//e.printStackTrace();
							throw new RuntimeException("Could not parse JSON data: " + e);
						}
		            	catch (UnsupportedEncodingException e) {
							//e.printStackTrace();
							throw new RuntimeException("Could not decode JSON data: " + e);
		            	}
		            }
		        }
		    };
    	}
    	return bReceiver;
    }

    /** Populate the SoundPool*/
    public static void initSounds(Context context) {
        soundPool = new SoundPool(2, AudioManager.STREAM_MUSIC, 100);
	    soundPoolMap = new HashMap<Integer,Integer>();
	
	    soundPoolMap.put( FireHallSoundPlayer.SOUND_DINGLING, soundPool.load(context, R.raw.dingling, 2) );
	    soundPoolMap.put( FireHallSoundPlayer.SOUND_LOGIN, soundPool.load(context, R.raw.login, 2) );
	    soundPoolMap.put( FireHallSoundPlayer.SOUND_PAGE1, soundPool.load(context, R.raw.page1, 2) );
	    soundPoolMap.put( FireHallSoundPlayer.SOUND_PAGER_TONE_PG, soundPool.load(context, R.raw.pager_tone_pg, 2) );
    }    
    
    /** Play a given sound in the soundPool */
    public static void playSound(Context context, int soundID) {
	   if(soundPool == null || soundPoolMap == null){
	      initSounds(context);
	   }
       float volume = (float) 1.0; // whatever in the range = 0.0 to 1.0

       // play sound with same right and left volume, with a priority of 1, 
       // zero repeats (i.e play once), and a playback rate of 1f
       soundPool.play(soundPoolMap.get(soundID), volume, volume, 1, 0, 1f);
    }   
    
}

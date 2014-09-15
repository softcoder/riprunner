/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.gcm.riprunner.app;

import com.vejvoda.android.gcm.riprunner.app.R;
import com.google.android.gms.common.ConnectionResult;
import com.google.android.gms.common.GooglePlayServicesClient;
import com.google.android.gms.common.GooglePlayServicesUtil;
import com.google.android.gms.gcm.GoogleCloudMessaging;
import com.google.android.gms.location.LocationClient;
import com.google.android.gms.location.LocationListener;
import com.google.android.gms.location.LocationRequest;

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
import android.media.AudioManager;
import android.media.SoundPool;

/**
 * Main UI for the demo app.
 */
public class AppMainActivity extends ActionBarActivity implements
		GooglePlayServicesClient.ConnectionCallbacks,
		GooglePlayServicesClient.OnConnectionFailedListener, LocationListener {

    public static final String PROPERTY_REG_ID = "registration_id";
        
    public static final String PROPERTY_WEBSITE_URL = "host_url";
    public static final String PROPERTY_SENDER_ID 	= "sender_id";
    
    public static final String PROPERTY_FIREHALL_ID = "firehall_id";
    public static final String PROPERTY_USER_ID = "user_id";
    
    private static final String PROPERTY_APP_VERSION = "appVersion";
    private static final int PLAY_SERVICES_RESOLUTION_REQUEST = 9000;

    public enum CalloutStatusType {
		Paged(0),
		Notified(1),
		Responding(2),
		Cancelled(3),
		Complete(10);
		
		private int value;

        private CalloutStatusType(int value) {
                this.value = value;
        }
        public int valueOf() {
            return this.value;
        }
        public boolean isComplete() {
    		return (this.value == CalloutStatusType.Cancelled.valueOf() ||
    				this.value == CalloutStatusType.Complete.valueOf());
        	
        }
        static public boolean isComplete(String status) {
    		return (status != null &&
    				(status.equals(String.valueOf(CalloutStatusType.Cancelled.valueOf())) ||
    				(status.equals(String.valueOf(CalloutStatusType.Complete.valueOf())))));
        	
        }
        
    };
    
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
    MenuItem logout_menu = null;

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
    
    // The location client that receives GPS location updates
    LocationClient mLocationClient = null;
    // Milliseconds per second
    private static final int MILLISECONDS_PER_SECOND = 1000;
    // Update frequency in seconds
    public static final int UPDATE_INTERVAL_IN_SECONDS = 5;
    // Update frequency in milliseconds
    private static final long UPDATE_INTERVAL =
            MILLISECONDS_PER_SECOND * UPDATE_INTERVAL_IN_SECONDS;
    // The fastest update frequency, in seconds
    private static final int FASTEST_INTERVAL_IN_SECONDS = 1;
    // A fast frequency ceiling in milliseconds
    private static final long FASTEST_INTERVAL =
            MILLISECONDS_PER_SECOND * FASTEST_INTERVAL_IN_SECONDS;
    
    // Define an object that holds accuracy and frequency parameters
    LocationRequest mLocationRequest;    
    boolean mUpdatesRequested = true;
    
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
        	
        	setupGPSTracking();
        	
        	if(hasConfigItem(context,PROPERTY_WEBSITE_URL) && hasConfigItem(context,PROPERTY_SENDER_ID)) {
        		
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

	void setupGPSTracking() {
		if(mLocationRequest == null) {
			// Create the LocationRequest object
			mLocationRequest = LocationRequest.create();
			// Use high accuracy
			mLocationRequest.setPriority(
			        LocationRequest.PRIORITY_HIGH_ACCURACY);
			// Set the update interval to 5 seconds
			mLocationRequest.setInterval(UPDATE_INTERVAL);
			// Set the fastest update interval to 1 second
			mLocationRequest.setFastestInterval(FASTEST_INTERVAL);
		}		
		if(mLocationClient == null) {
			/*
			 * Create a new location client, using the enclosing class to
			 * handle callbacks.
			 */
			mLocationClient = new LocationClient(this, this, this);
		}
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
        //btnLogin.setText(getResources().getString(R.string.login));
        btnLogin.setEnabled(true);
        btnLogin.setVisibility(View.VISIBLE);
        
        Button btnMap = (Button)findViewById(R.id.btnMap);
        btnMap.setEnabled(false);
        btnMap.setVisibility(View.INVISIBLE);
        Button btnRespond = (Button)findViewById(R.id.btnRespond);
        btnRespond.setEnabled(false);
        btnRespond.setVisibility(View.INVISIBLE);
        
        Button btnCompleteCall = (Button)findViewById(R.id.btnCompleteCall);
        btnCompleteCall.setEnabled(false);
        btnCompleteCall.setVisibility(View.INVISIBLE);
        
        Button btnCancelCall = (Button)findViewById(R.id.btnCancelCall);
        btnCancelCall.setEnabled(false);
        btnCancelCall.setVisibility(View.INVISIBLE);
        
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
    protected void onStart() {
    	super.onStart();
        // Connect the client.
        mLocationClient.connect();
    }

    /*
     * Called when the Activity is no longer visible at all.
     * Stop updates and disconnect.
     */
    @Override
    protected void onStop() {
        // If the client is connected
        if (mLocationClient.isConnected()) {
            /*
             * Remove location updates for a listener.
             * The current Activity is the listener, so
             * the argument is "this".
             */
            //removeLocationUpdates(this);
        }
        /*
         * After disconnect() is called, the client is
         * considered "dead".
         */
        mLocationClient.disconnect();
        super.onStop();
    }
    
    @Override
    protected void onPause() {
        // Save the current setting for updates
        //mEditor.putBoolean("KEY_UPDATES_ON", mUpdatesRequested);
        //mEditor.commit();
        super.onPause();
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

    private String getGcmDeviceRegistrationId(boolean forceNewId) throws IOException {
        if (gcm == null) {
            gcm = GoogleCloudMessaging.getInstance(context);
        }
        
        String regid = getConfigItem(context,PROPERTY_REG_ID);
        if (forceNewId == true || regid.isEmpty()) {
        	regid = gcm.register(getConfigItem(context,PROPERTY_SENDER_ID));

        	// Persist the regID - no need to register again.
        	storeConfigItem(context, PROPERTY_REG_ID, regid);
        }
    	return regid;
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
//                    if (gcm == null) {
//                        gcm = GoogleCloudMessaging.getInstance(context);
//                    }
//                    
//                    String regid = getConfigItem(context,PROPERTY_REG_ID);
//                    if (regid.isEmpty()) {
//                    	regid = gcm.register(getConfigItem(context,PROPERTY_SENDER_ID));
//
//                    	// Persist the regID - no need to register again.
//                    	storeConfigItem(context, PROPERTY_REG_ID, regid);
//                    }
                	String regid = getGcmDeviceRegistrationId(false);
                	
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
                    Log.e("registerInBackground()::doInBackground()", "Error", ex);
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
    private void respondInBackground(final CalloutStatusType statusType) {
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
                    sendResponseToBackend(auth,statusType);
                } 
                catch (IOException ex) {
                    msg = "Error :" + ex.getMessage();
                    // If there is an error, don't just keep trying to register.
                    // Require the user to click a button again, or perform
                    // exponential back-off.
                    Log.e("respondInBackground()::doInBackground()", "Error statusType" + statusType, ex);
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
                    	if(isLoggedIn() == false) {
                    		registerInBackground();
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
			handleCalloutMapView();
        }
        else if (view == findViewById(R.id.btnRespond)) {
            new AsyncTask<Void, Void, String>() {
                @Override
                protected String doInBackground(Void... params) {

                    // Check device for Play Services APK. If check succeeds, proceed with GCM registration.
                    if (checkPlayServices()) {
                    	if(isLoggedIn()) {
                    		respondInBackground(CalloutStatusType.Responding);
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
        else if (view == findViewById(R.id.btnCompleteCall)) {
            new AsyncTask<Void, Void, String>() {
                @Override
                protected String doInBackground(Void... params) {

                    // Check device for Play Services APK. If check succeeds, proceed with GCM registration.
                    if (checkPlayServices()) {
                    	if(isLoggedIn()) {
                    		respondInBackground(CalloutStatusType.Complete);
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
        else if (view == findViewById(R.id.btnCancelCall)) {
            new AsyncTask<Void, Void, String>() {
                @Override
                protected String doInBackground(Void... params) {

                    // Check device for Play Services APK. If check succeeds, proceed with GCM registration.
                    if (checkPlayServices()) {
                    	if(isLoggedIn()) {
                    		respondInBackground(CalloutStatusType.Cancelled);
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
    }

    private void handleCalloutMapView() {
    	try {
//			String uri = String.format(Locale.ENGLISH, "geo:%s,%s?q=%s", 
//					URLEncoder.encode(lastCallout.getGPSLat(), "utf-8"), 
//					URLEncoder.encode(lastCallout.getGPSLong(), "utf-8"),
//					URLEncoder.encode(lastCallout.getMapAddress(), "utf-8"));
//			
//        	Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
//        	intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
    		
    		String uri = String.format(Locale.ENGLISH, "http://maps.google.com/maps?daddr=%s,%s (%s)", 
					URLEncoder.encode(lastCallout.getGPSLat(), "utf-8"), 
					URLEncoder.encode(lastCallout.getGPSLong(), "utf-8"),
					URLEncoder.encode(lastCallout.getMapAddress(), "utf-8"));
    		Intent intent = new Intent(android.content.Intent.ACTION_VIEW,Uri.parse(uri));
    		intent.setClassName("com.google.android.apps.maps", "com.google.android.maps.MapsActivity");
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
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                try {
                	context.startActivity(intent);
                }
                catch(ActivityNotFoundException innerEx) {
                	Log.e("main::onClick map", "Error", innerEx);
                    Toast.makeText(this, "Please install a maps application", Toast.LENGTH_LONG).show();
                }
        	}
		} 
		catch (UnsupportedEncodingException e) {
			// TODO Auto-generated catch block
			//e.printStackTrace();
			Log.e("main::onClick map2", "Error", e);
			Toast.makeText(this, "UnsupportedEncodingException: " + e.getMessage(), Toast.LENGTH_LONG).show();
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
        	Log.e("getAppVersion()", "Error", e);
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
    
    private boolean isGcmErrorNotRegistered(String responseString) {
    	//|GCM_ERROR:
    	if(responseString != null && responseString.contains("|GCM_ERROR:")) {
    		return true;
    	}
    	return false;
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
            if(isGcmErrorNotRegistered(responseString)) {
            	String regid = getGcmDeviceRegistrationId(true);
            	auth.setGCMRegistrationId(regid);
            	sendRegistrationIdToBackend(auth);
            	return;
            }
            		
            if(responseString != null && responseString.startsWith("OK=")) {
	            storeConfigItem(context, PROPERTY_USER_ID, auth.getUserId());
	            auth.setRegisteredBackend(true);
	            
	            runOnUiThread(new Runnable() {
	                public void run() {
	                	
	                    Button btnLogin = (Button)findViewById(R.id.btnLogin);
	                    //btnLogin.setText(getResources().getString(R.string.logout));
	                    btnLogin.setEnabled(false);
	                    btnLogin.setVisibility(View.INVISIBLE);
	                	
	                    TextView txtMsg = (TextView)findViewById(R.id.txtMsg);
	                    txtMsg.setText(getResources().getString(R.string.login_success));

	                    // Enable when debugging
	                    //mDisplay.setText(responseString);
	                    
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
    private void sendResponseToBackend(FireHallAuthentication auth,
    		final CalloutStatusType statusType) throws ClientProtocolException, IOException {
    	
    	List<NameValuePair> params = new LinkedList<NameValuePair>();
    	params.add(new BasicNameValuePair("cid", lastCallout.getCalloutId()));
    	params.add(new BasicNameValuePair("ckid", lastCallout.getCalloutKeyId()));
    	params.add(new BasicNameValuePair("fhid", auth.getFirehallId()));
    	params.add(new BasicNameValuePair("uid", auth.getUserId()));
    	params.add(new BasicNameValuePair("upwd", auth.getUserPassword()));
   		params.add(new BasicNameValuePair("lat", String.valueOf(getLastGPSLatitude())));
   		params.add(new BasicNameValuePair("long", String.valueOf(getLastGPSLongitude())));
    	params.add(new BasicNameValuePair("status", String.valueOf(statusType.valueOf())));
    	
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
	                    btnRespond.setVisibility(View.VISIBLE);
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
                        txtMsg.setText("Invalid server response: " + (responseString != null ? responseString : "null"));
                        
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
                    txtMsg.setText("Error during server response: " + (errorText != null ? errorText : "null"));
                    
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
    }
	
	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
	    // Inflate the menu items for use in the action bar
	    MenuInflater inflater = getMenuInflater();
	    inflater.inflate(R.menu.main_activity_actions, menu);
	    logout_menu = (MenuItem) menu.findItem(R.id.action_logout);
	    return super.onCreateOptionsMenu(menu);
	}
	
	@Override
	public boolean onPrepareOptionsMenu (Menu menu) {
	
		logout_menu.setEnabled(isLoggedIn()); // here pass the index of save menu item
		return super.onPrepareOptionsMenu(menu);
	}
	
	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
	    // Handle presses on the action bar items
	    switch (item.getItemId()) {
	        case R.id.action_logout:
	            logout();
	            return true;
	        case R.id.action_clear:
	            clearUI();
	            return true;
	        case R.id.action_mapmygps:
	            mapCurrentLocation();
	            return true;
	    
	        case R.id.action_settings:
	            openSettings();
	            return true;
	        default:
	            return super.onOptionsItemSelected(item);
	    }
	}

	private void mapCurrentLocation() {
    	try {
    		
//			String uri = String.format(Locale.ENGLISH, "geo:%s,%s?q=%s", 
//					URLEncoder.encode(lastCallout.getGPSLat(), "utf-8"), 
//					URLEncoder.encode(lastCallout.getGPSLong(), "utf-8"),
//					URLEncoder.encode(lastCallout.getMapAddress(), "utf-8"));
//			
//        	Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
//        	intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
            
    		String uri = String.format(Locale.ENGLISH, "http://maps.google.com/maps?q=%s,%s", 
					URLEncoder.encode(String.valueOf(getLastGPSLatitude()), "utf-8"), 
					URLEncoder.encode(String.valueOf(getLastGPSLongitude()), "utf-8"));
    		Intent intent = new Intent(android.content.Intent.ACTION_VIEW,Uri.parse(uri));
    		intent.setClassName("com.google.android.apps.maps", "com.google.android.maps.MapsActivity");
    		intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
            
        	if (intent.resolveActivity(getPackageManager()) != null) {
        		context.startActivity(intent);
        	}
        	else {
        		uri = String.format(Locale.ENGLISH, "http://maps.google.com/maps?q=%s,%s", 
    					URLEncoder.encode(String.valueOf(getLastGPSLatitude()), "utf-8"), 
    					URLEncoder.encode(String.valueOf(getLastGPSLongitude()), "utf-8"));
                intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                try {
                	context.startActivity(intent);
                }
                catch(ActivityNotFoundException innerEx) {
                	Log.e("main::onClick map", "Error", innerEx);
                    Toast.makeText(this, "Please install a maps application", Toast.LENGTH_LONG).show();
                }
        	}
		} 
		catch (UnsupportedEncodingException e) {
			// TODO Auto-generated catch block
			//e.printStackTrace();
			Log.e("main::onClick map2", "Error", e);
			Toast.makeText(this, "UnsupportedEncodingException: " + e.getMessage(), Toast.LENGTH_LONG).show();
		}
	}
	
	private boolean isLoggedIn() {
		return(auth != null && auth.getRegisteredBackend());
	}
	
	private void logout() {
        // Check device for Play Services APK. If check succeeds, proceed with GCM registration.
        if (checkPlayServices()) {
        	if(auth != null) {
        		//stopGPSTracking();
        		
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
	}
	
	private void clearUI() {
        mDisplay.setText("");
        //stopGPSTracking();
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

							if(json.has("DEVICE_MSG")) {
								// Do Nothing.
								final String deviceMsg = URLDecoder.decode(json.getString("DEVICE_MSG"), "utf-8");
								if(deviceMsg != null && deviceMsg.equals("GCM_LOGINOK") == false) {
									runOnUiThread(new Runnable() {
									    public void run() {
									    	mDisplay = (TextView) findViewById(R.id.display);
									    	mDisplay.append("\n" + deviceMsg);
									   }
									});
								}
							}
							else if(json.has("CALLOUT_MSG")) {
								processCalloutTrigger(json);
							}
							else if(json.has("CALLOUT_RESPONSE_MSG")) {
								processCalloutResponseTrigger(json);       
							}
						}
		            	catch (JSONException e) {
							//e.printStackTrace();
		            		Log.e("getBroadCastReceiver()", serviceJsonString, e);
		            		
							throw new RuntimeException("Could not parse JSON data: " + e);
						}
		            	catch (UnsupportedEncodingException e) {
							//e.printStackTrace();
		            		Log.e("getBroadCastReceiver()", serviceJsonString, e);
							throw new RuntimeException("Could not decode JSON data: " + e);
		            	}
		            }
		        }

				void processCalloutResponseTrigger(JSONObject json)
						throws UnsupportedEncodingException, JSONException {
					final String calloutMsg = URLDecoder.decode(json.getString("CALLOUT_RESPONSE_MSG"), "utf-8");

					String callout_id = URLDecoder.decode(json.getString("call-id"), "utf-8");
					String callout_status = URLDecoder.decode(json.getString("user-status"), "utf-8");
					if(lastCallout != null) {
						if(lastCallout.getCalloutId() == callout_id) {
							if(lastCallout.getStatus() != callout_status) {
								lastCallout.setStatus(callout_status);
							}
						}
					}
					runOnUiThread(new Runnable() {
					    public void run() {
					    	mDisplay = (TextView) findViewById(R.id.display);
					    	mDisplay.append("\n" + calloutMsg);

					    	if(lastCallout != null &&
					    		CalloutStatusType.isComplete(lastCallout.getStatus()) == false) {
					    		
					            Button btnCompleteCall = (Button)findViewById(R.id.btnCompleteCall);
					            btnCompleteCall.setVisibility(View.VISIBLE);
					            btnCompleteCall.setEnabled(true);

					            Button btnCancelCall = (Button)findViewById(R.id.btnCancelCall);
					            btnCancelCall.setVisibility(View.VISIBLE);
					            btnCancelCall.setEnabled(true);
					    	}
					    	else {
					            Button btnCompleteCall = (Button)findViewById(R.id.btnCompleteCall);
					            btnCompleteCall.setVisibility(View.VISIBLE);
					            btnCompleteCall.setEnabled(false);
					            
					            Button btnCancelCall = (Button)findViewById(R.id.btnCancelCall);
					            btnCancelCall.setVisibility(View.VISIBLE);
					            btnCancelCall.setEnabled(false);
					    	}
					    	
					    	playSound(AppMainActivity.this.context,FireHallSoundPlayer.SOUND_DINGLING);
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
						Log.e("getBroadCastReceiver()", calloutMsg, e);
						
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
					
					lastCallout = new FireHallCallout(
							URLDecoder.decode(json.getString("call-id"), "utf-8"),
							callKeyId,
							gpsLatStr,gpsLongStr,
							callAddress,
							callMapAddress,
							URLDecoder.decode(json.getString("call-units"), "utf-8"),
							URLDecoder.decode(json.getString("call-status"), "utf-8"));
					
					runOnUiThread(new Runnable() {
					    public void run() {

					    	mDisplay = (TextView) findViewById(R.id.display);
					    	mDisplay.setText(calloutMsg);
					    	
					    	playSound(AppMainActivity.this.context,FireHallSoundPlayer.SOUND_PAGER_TONE_PG);
					    	
					        Button btnMap = (Button)findViewById(R.id.btnMap);
					        btnMap.setVisibility(View.VISIBLE);
					        btnMap.setEnabled(true);
					        
					        Button btnRespond = (Button)findViewById(R.id.btnRespond);
					        btnRespond.setVisibility(View.VISIBLE);
					        btnRespond.setEnabled(true);
					        
					    	if(CalloutStatusType.isComplete(lastCallout.getStatus()) == false) {
				                Button btnCompleteCall = (Button)findViewById(R.id.btnCompleteCall);
				                btnCompleteCall.setVisibility(View.VISIBLE);
				                btnCompleteCall.setEnabled(true);
				                
					            Button btnCancelCall = (Button)findViewById(R.id.btnCancelCall);
					            btnCancelCall.setVisibility(View.VISIBLE);
					            btnCancelCall.setEnabled(true);
					    	}
					    	else {
					            Button btnCompleteCall = (Button)findViewById(R.id.btnCompleteCall);
					            btnCompleteCall.setVisibility(View.VISIBLE);
					            btnCompleteCall.setEnabled(false);
					            
					            Button btnCancelCall = (Button)findViewById(R.id.btnCancelCall);
					            btnCancelCall.setVisibility(View.VISIBLE);
					            btnCancelCall.setEnabled(false);
					    	}
					   }
					});
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

	@Override
	public void onConnectionFailed(ConnectionResult arg0) {
		// TODO Auto-generated method stub
	}

	@Override
	public void onConnected(Bundle arg0) {
		// Display the connection status
        Toast.makeText(this, "GPS Connected", Toast.LENGTH_SHORT).show();

        // If already requested, start periodic updates
        if (mUpdatesRequested) {
            mLocationClient.requestLocationUpdates(mLocationRequest, this);
        }		
	}

	@Override
	public void onDisconnected() {
		// Display the connection status
        Toast.makeText(this, "GPS Disconnected.",Toast.LENGTH_SHORT).show();
	}

	@Override
    public void onLocationChanged(Location location) {
		//this.location = location;
		// Report to the UI that the location was updated
		
		// Debug GPS values
//        String msg = "Updated GPS Location: " +
//                Double.toString(location.getLatitude()) + "," +
//                Double.toString(location.getLongitude());
//        Toast.makeText(this, msg, Toast.LENGTH_SHORT).show();
	}

	double getLastGPSLatitude() {
		if(mLocationClient == null) {
			return 0;
		}
        Location location = mLocationClient.getLastLocation();
        double lat = (location != null ? location.getLatitude() : 0);
        return lat;
	}
	double getLastGPSLongitude() {
		if(mLocationClient == null) {
			return 0;
		}

		Location location = mLocationClient.getLastLocation();
        double lng = (location != null ? location.getLongitude() : 0);
        return lng;
	}
}

/*
/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */
package com.vejvoda.android.riprunner;

import com.google.android.gms.appindexing.Action;
import com.google.android.gms.appindexing.AppIndex;
import com.google.android.gms.common.GoogleApiAvailability;
import com.vejvoda.android.riprunner.FireHallCallout.Responder;
import com.google.android.gms.common.ConnectionResult;
import com.google.android.gms.common.api.GoogleApiClient;
import com.google.android.gms.location.LocationListener;
import com.google.android.gms.location.LocationRequest;
import com.google.android.gms.location.LocationServices;
import com.google.android.gms.maps.CameraUpdate;
import com.google.android.gms.maps.CameraUpdateFactory;
import com.google.android.gms.maps.GoogleMap;
import com.google.android.gms.maps.OnMapReadyCallback;
import com.google.android.gms.maps.model.BitmapDescriptorFactory;
import com.google.android.gms.maps.model.LatLng;
import com.google.android.gms.maps.model.LatLngBounds;
import com.google.android.gms.maps.model.Marker;
import com.google.android.gms.maps.model.MarkerOptions;
import com.google.android.gms.maps.model.Polyline;
import com.google.android.gms.maps.model.PolylineOptions;

import android.Manifest;
import android.app.AlarmManager;
import android.app.AlertDialog;
import android.app.PendingIntent;
import android.app.ProgressDialog;
import android.content.ActivityNotFoundException;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.content.pm.PackageManager.NameNotFoundException;
import android.graphics.Color;
import android.media.AudioAttributes;
import android.net.Uri;
import android.os.AsyncTask;
import android.os.Bundle;
import android.os.SystemClock;
import android.os.Vibrator;
import android.preference.PreferenceManager;
import android.support.annotation.NonNull;
import android.support.v4.app.ActivityCompat;
import android.support.v4.app.Fragment;
import android.support.v4.content.ContextCompat;
import android.support.v4.content.LocalBroadcastManager;
import android.support.v7.app.AppCompatActivity;
import android.text.method.ScrollingMovementMethod;
import android.util.Log;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.inputmethod.InputMethodManager;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ScrollView;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.net.URLDecoder;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Vector;

import org.json.JSONException;
import org.json.JSONObject;

import android.location.Location;
import android.media.AudioManager;
import android.media.Ringtone;
import android.media.RingtoneManager;
import android.media.SoundPool;

/**
 * Main UI for the Rip Runner Android app.
 */
public class AppMainActivity extends AppCompatActivity implements
        GoogleApiClient.ConnectionCallbacks,
        GoogleApiClient.OnConnectionFailedListener,
        LocationListener, OnMapReadyCallback, AdapterView.OnItemSelectedListener {

    TextView mDisplay;
    ScrollView mDisplayScroll;

    Context context;
    MenuItem logout_menu;
    ProgressDialog loadingDlg;

    /**
     * The authentication object
     */
    FireHallAuthentication auth;
    /**
     * The last callout information
     */
    FireHallCallout lastCallout;

    /**
     * The broadcast receiver class for getting broadcast messages
     */
    private BroadcastReceiver broadcastReceiver;

    // The location client that receives GPS location updates
    private GoogleApiClient googleApiClient;

    // Google Map related
    private ScrollableMapFragment mapFragment;
    private GoogleMap inlineMap;
    private List<Marker> mapMarkers;
    private Marker currentUserMarker;
    private KMLData kmlData;

    // Milliseconds per second
    private static final int MILLISECONDS_PER_SECOND = 1000;
    // Update frequency in seconds
    public static final int UPDATE_INTERVAL_IN_SECONDS = 60;
    // Update frequency in milliseconds
    private static final long UPDATE_INTERVAL =
            MILLISECONDS_PER_SECOND * UPDATE_INTERVAL_IN_SECONDS;
    // The fastest update frequency, in seconds
    private static final int FASTEST_INTERVAL_IN_SECONDS = 20;
    // A fast frequency ceiling in milliseconds
    private static final long FASTEST_INTERVAL =
            MILLISECONDS_PER_SECOND * FASTEST_INTERVAL_IN_SECONDS;

    // Define an object that holds accuracy and frequency parameters
    private LocationRequest locationRequest;
    private Location lastTrackedGEOLocation;
    private boolean updatesRequested = true;
    private PendingIntent geoTrackingIntent;

    private int gcmLoginErrorCount = 0;
    /**
     * ATTENTION: This was auto-generated to implement the App Indexing API.
     * See https://g.co/AppIndexing/AndroidStudio for more information.
     */
    private GoogleApiClient client;

    /**
     * This class contains a wrapper for accessing predefined sounds
     *
     * @author softcoder
     */
    public class FireHallSoundPlayer {
        public static final int SOUND_DINGLING = R.raw.dingling;
        public static final int SOUND_LOGIN = R.raw.login;
        public static final int SOUND_PAGE1 = R.raw.page1;
        public static final int SOUND_PAGER_TONE_PG = R.raw.pager_tone_pg;
    }

    private static SoundPool soundPool;
    private static Map<Integer, Integer> soundPoolMap;


    private BroadcastReceiver activityReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            try {
                Log.i(Utils.TAG, Utils.getLineNumber() + ": MainApp Broadcaster got intent action: " + (intent == null ? "null" : intent));

                if (intent != null && intent.getAction() != null) {
                    Log.i(Utils.TAG, Utils.getLineNumber() + ": MainApp Broadcaster got intent action: " + intent.getAction());

                    if (intent.getAction().equals(Utils.REGISTRATION_COMPLETE_MAIN)) {
                        //processRecieveCalloutMsg(intent);
                        getProgressDialog().hide();
                    } else if (intent.getAction().equals(Utils.RECEIVE_CALLOUT_MAIN)) {
                        processRecieveCalloutMsg(intent);
                    } else if (intent.getAction().equals(Utils.TRACKING_GEO_MAIN)) {
                        processTrackingGeoCoordinates();
                    } else {
                        Log.e(Utils.TAG, Utils.getLineNumber() + ": MainApp Broadcaster got ***UNHANDLED*** intent action: " + intent.getAction());
                    }
                } else {
                    Log.e(Utils.TAG, Utils.getLineNumber() + ": MainApp Broadcaster Error null intent or action.");
                }
            } catch (Exception e) {
                Log.e(Utils.TAG, Utils.getLineNumber() + ": ****** Rip Runner Error ******", e);
                throw e;
            }
        }

        private void processTrackingGeoCoordinates() {
            Boolean tracking_enabled = isTrackingEnabled();
            if (tracking_enabled != null && tracking_enabled) {
                new AsyncTask<Void, Void, String>() {
                    @Override
                    protected String doInBackground(Void... params) {
                        try {
                            sendGeoTrackingToBackend();
                        } catch (Exception e) {
                            Log.e(Utils.TAG, Utils.getLineNumber() + ": GEO Tracking", e);
                            throw new RuntimeException("Error with GEO Tracking: " + e);
                        }

                        return "";
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

                JSONObject json = new JSONObject(serviceJsonString);

                if (json.has("DEVICE_MSG")) {
                    processDeviceMsgTrigger(json);
                } else if (json.has("CALLOUT_MSG")) {
                    processCalloutTrigger(json);
                } else if (json.has("CALLOUT_RESPONSE_MSG")) {
                    processCalloutResponseTrigger(json);
                } else if (json.has("ADMIN_MSG")) {
                    processAdminMsgTrigger(json);
                } else {
                    Log.e(Utils.TAG, Utils.getLineNumber() + ": Broadcaster got UNKNOWN callout message type: " + json.toString());
                }
            } catch (JSONException e) {
                Log.e(Utils.TAG, Utils.getLineNumber() + ": " + serviceJsonString, e);
                throw new RuntimeException("Could not parse JSON data: " + e);
            } catch (UnsupportedEncodingException e) {
                Log.e(Utils.TAG, Utils.getLineNumber() + ": " + serviceJsonString, e);
                throw new RuntimeException("Could not decode JSON data: " + e);
            } catch (Exception e) {
                Log.e(Utils.TAG, Utils.getLineNumber() + ": " + serviceJsonString, e);
                throw new RuntimeException("Error with JSON data: " + e);
            }
        }

        void processAdminMsgTrigger(JSONObject json)
                throws UnsupportedEncodingException, JSONException {
            final String adminMsg = URLDecoder.decode(json.getString("ADMIN_MSG"), "utf-8");
            if (adminMsg != null) {
                AppMainActivity.this.processAdminMsgTrigger(adminMsg);
            }
        }

        void processDeviceMsgTrigger(JSONObject json)
                throws UnsupportedEncodingException, JSONException {
            final String deviceMsg = URLDecoder.decode(json.getString("DEVICE_MSG"), "utf-8");
            if (deviceMsg != null && !deviceMsg.equals("GCM_LOGINOK")) {
                AppMainActivity.this.processDeviceMsgTrigger(deviceMsg);
            }
        }

        void processCalloutResponseTrigger(JSONObject json)
                throws UnsupportedEncodingException, JSONException {
            final String calloutMsg = URLDecoder.decode(json.getString("CALLOUT_RESPONSE_MSG"), "utf-8");

            String callout_id = URLDecoder.decode(json.getString("call-id"), "utf-8");
            String callout_status = URLDecoder.decode(json.getString("user-status"), "utf-8");
            String response_userid = URLDecoder.decode(json.getString("user-id"), "utf-8");

            AppMainActivity.this.processCalloutResponseTrigger(calloutMsg, callout_id,
                    callout_status, response_userid);
        }

        void processCalloutTrigger(JSONObject json)
                throws UnsupportedEncodingException, JSONException {
            final String calloutMsg = URLDecoder.decode(json.getString("CALLOUT_MSG"), "utf-8");

            String gpsLatStr;
            String gpsLongStr;

            try {
                gpsLatStr = URLDecoder.decode(json.getString("call-gps-lat"), "utf-8");
                gpsLongStr = URLDecoder.decode(json.getString("call-gps-long"), "utf-8");
            } catch (Exception e) {
                Log.e(Utils.TAG, Utils.getLineNumber() + ": " + calloutMsg, e);

                throw new RuntimeException("Could not parse JSON data: " + e);
            }

            String callKeyId = URLDecoder.decode(json.getString("call-key-id"), "utf-8");
            if (callKeyId == null || callKeyId.equals("?")) {
                callKeyId = "";
            }
            String callAddress = URLDecoder.decode(json.getString("call-address"), "utf-8");
            if (callAddress == null || callAddress.equals("?")) {
                callAddress = "";
            }
            String callMapAddress = URLDecoder.decode(json.getString("call-map-address"), "utf-8");
            if (callMapAddress == null || callMapAddress.equals("?")) {
                callMapAddress = "";
            }
            String callType = "?";
            if (json.has("call-type")) {
                callType = URLDecoder.decode(json.getString("call-type"), "utf-8");
            }

            AppMainActivity.this.processCalloutTrigger(
                    URLDecoder.decode(json.getString("call-id"), "utf-8"),
                    callKeyId,
                    callType,
                    gpsLatStr, gpsLongStr,
                    callAddress,
                    callMapAddress,
                    URLDecoder.decode(json.getString("call-units"), "utf-8"),
                    URLDecoder.decode(json.getString("call-status"), "utf-8"),
                    calloutMsg);
        }

    };

    @Override
    public void onCreate(Bundle savedInstanceState) {
        try {
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Starting up Rip Runner.");
            super.onCreate(savedInstanceState);

            if (getSupportActionBar() != null) {
                getSupportActionBar().setDisplayShowHomeEnabled(true);
                getSupportActionBar().setLogo(R.drawable.ic_launcher);
                getSupportActionBar().setDisplayUseLogoEnabled(true);
            }

            setContentView(R.layout.main);
            mDisplay = (TextView) findViewById(R.id.display);
            mDisplayScroll = (ScrollView) findViewById(R.id.textAreaScroller);

            context = getApplicationContext();
            initSounds(context);

            setupLocalBroadcastManager();
            getProgressDialog();

            EditText etFhid = (EditText) findViewById(R.id.etFhid);
            EditText etUid = (EditText) findViewById(R.id.etUid);
            EditText etUpw = (EditText) findViewById(R.id.etUpw);

            etFhid.setSelectAllOnFocus(true);
            etUid.setSelectAllOnFocus(true);
            setupLoginUI();

            boolean focusPWd = false;
            if (checkPlayServices()) {
                setupGCMRegistration(false);

                checkGeoPermission();
                setupGPSTracking();
                setupMapFragment();

                if (hasConfigItem(context, AppConstants.PROPERTY_WEBSITE_URL, String.class) &&
                        !getConfigItem(context, AppConstants.PROPERTY_WEBSITE_URL, String.class).isEmpty() &&
                        hasConfigItem(context, AppConstants.PROPERTY_SENDER_ID, String.class) &&
                        !getConfigItem(context, AppConstants.PROPERTY_SENDER_ID, String.class).isEmpty() &&
                        hasConfigItem(context, AppConstants.PROPERTY_TRACKING_ENABLED, Boolean.class) &&
                        hasConfigItem(context, AppConstants.PROPERTY_LOGIN_PAGE_URI, String.class) &&
                        hasConfigItem(context, AppConstants.PROPERTY_CALLOUT_PAGE_URI, String.class) &&
                        hasConfigItem(context, AppConstants.PROPERTY_RESPOND_PAGE_URI, String.class) &&
                        hasConfigItem(context, AppConstants.PROPERTY_TRACKING_PAGE_URI, String.class) &&
                        hasConfigItem(context, AppConstants.PROPERTY_KML_PAGE_URI, String.class) &&
                        hasConfigItem(context, AppConstants.PROPERTY_ANDROID_ERROR_PAGE_URI, String.class) &&
                        hasConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class) &&
                        !getConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class).isEmpty()) {

                    etFhid.setText(getConfigItem(context, AppConstants.PROPERTY_FIREHALL_ID, String.class));
                    etUid.setText(getConfigItem(context, AppConstants.PROPERTY_USER_ID, String.class));
                    setupResponseStatuses();
                    startGEOAlarm();
                    focusPWd = true;
                } else {
                    openSettings(true);
                }
                etUpw.setText("");
            } else {
                Log.i(Utils.TAG, Utils.getLineNumber() + ": No valid Google Play Services APK found.");
            }

            etUid.requestFocus();
            etFhid.requestFocus();
            if (focusPWd) {
                etUpw.requestFocus();
            }
        } catch (Exception e) {
            e.printStackTrace();
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            getProgressDialog().hide();
        }
        // ATTENTION: This was auto-generated to implement the App Indexing API.
        // See https://g.co/AppIndexing/AndroidStudio for more information.
        client = new GoogleApiClient.Builder(this).addApi(AppIndex.API).build();
    }

    private void setupResponseStatuses() {
        /* Spinner Drop down elements */
        String jsonStatusDef = getConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class);
        try {

            List<String> statusList = FireHallCalloutStatus.getResponseStatuses(jsonStatusDef);
            int defaultResponseIndex = FireHallCalloutStatus.getDefaultResponseStatusIndex(jsonStatusDef);

            // Spinner element
            Spinner spinner = (Spinner) findViewById(R.id.spinRespond);
            spinner.setOnItemSelectedListener(null);

            // Creating adapter for spinner
            ArrayAdapter<String> dataAdapter = new ArrayAdapter<String>(this, android.R.layout.simple_spinner_item, statusList);
            // Drop down layout style - list view with radio button
            dataAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);

            // attaching data adapter to spinner
            spinner.setAdapter(dataAdapter);
            if(defaultResponseIndex >= 0) {
                spinner.setSelection(defaultResponseIndex);
            }
            // Spinner click listener
            spinner.setOnItemSelectedListener(this);
        }
        catch (JSONException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": *****Error***** - parsing status JSON",e);
            Toast.makeText(this, "Error - parsing status JSON, msg: " + e.getMessage(), Toast.LENGTH_SHORT).show();
        }
    }

    @Override
    public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
        // On selecting a spinner item
        //String item = parent.getItemAtPosition(position).toString();
        // Showing selected spinner item
        //Toast.makeText(parent.getContext(), "Selected: " + item, Toast.LENGTH_LONG).show();
    }

    public void onNothingSelected(AdapterView<?> arg0) {
        // TODO Auto-generated method stub
    }

    private void setupGCMRegistration(boolean forceReg) {
        if (forceReg || (!hasConfigItem(context, AppConstants.GOT_TOKEN_FROM_SERVER, Boolean.class) ||
                !getConfigItem(context, AppConstants.GOT_TOKEN_FROM_SERVER, Boolean.class) ||
                !hasConfigItem(context, AppConstants.PROPERTY_REG_ID, String.class) ||
                getConfigItem(context, AppConstants.PROPERTY_REG_ID, String.class).isEmpty())) {
            if (hasConfigItem(context, AppConstants.PROPERTY_SENDER_ID, String.class) &&
                    !getConfigItem(context, AppConstants.PROPERTY_SENDER_ID, String.class).isEmpty()) {
                String senderId = getConfigItem(context, AppConstants.PROPERTY_SENDER_ID, String.class);
                // Start IntentService to register this application with GCM.
                showProgressDialog(true, "Checking for registration token...");

                Intent intent = new Intent(this, RegistrationIntentService.class);
                intent.putExtra(AppConstants.PROPERTY_SENDER_ID, senderId);
                startService(intent);
            }
        }
    }

    private void setupLocalBroadcastManager() {
        LocalBroadcastManager localBroadcastManager = LocalBroadcastManager.getInstance(this);
        localBroadcastManager.registerReceiver(getBroadCastReceiver(), Utils.getBroadCastReceiverIntentFilter());
    }

    private void setupMapFragment() {
        if (mapFragment == null) {
            mapFragment = ((ScrollableMapFragment) getSupportFragmentManager().findFragmentById(R.id.map));
            final ScrollView scrollView = (ScrollView) findViewById(R.id.scrollMap);
            mapFragment.setListener(new ScrollableMapFragment.OnTouchListener() {
                @Override
                public void onTouch() {
                    scrollView.requestDisallowInterceptTouchEvent(true);
                }
            });

            mapFragment.getMapAsync(this);
        }
    }

    @Override
    public void onMapReady(GoogleMap map) {
        this.inlineMap = map;
        if (this.inlineMap != null) {
            //Toast.makeText(this, "Map Fragment was loaded properly!", Toast.LENGTH_SHORT).show();
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Map Fragment was loaded properly.");

            checkGeoPermission();
            this.inlineMap.setMyLocationEnabled(true);
            this.inlineMap.setTrafficEnabled(true);
        } else {
            Log.i(Utils.TAG, Utils.getLineNumber() + ": *****Error***** - Map was null!!");
            Toast.makeText(this, "Error - Map was null!!", Toast.LENGTH_SHORT).show();
        }
    }

    public Boolean isTrackingEnabled() {
        return getConfigItem(context, AppConstants.PROPERTY_TRACKING_ENABLED, Boolean.class);
    }
    /*
    public boolean isUserLoggedOn(String userId) {
    	boolean result = false;
    	if(auth != null && userId != null &&
    			auth.getUserId().equals(userId)) {
    		result = true;
    	}
    	return result;
    }
    */

    @Override
    public void onSaveInstanceState(Bundle savedInstanceState) {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Saving instance state for Rip Runner.");

        // Always call the superclass so it can save the view hierarchy state
        savedInstanceState.putString("WORKAROUND_FOR_BUG_19917_KEY", "WORKAROUND_FOR_BUG_19917_VALUE");
        super.onSaveInstanceState(savedInstanceState);
    }

    private PendingIntent getGeoTrackingIntent() {
        if (geoTrackingIntent == null) {
            Intent intent = new Intent(this, AppMainBroadcastReceiver.class);
            intent.setAction(Utils.TRACKING_GEO);
            geoTrackingIntent = PendingIntent.getBroadcast(this, 0, intent, PendingIntent.FLAG_CANCEL_CURRENT);
        }
        return geoTrackingIntent;
    }

    void startGEOAlarm() {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner in startGEOAlarm: " + (geoTrackingIntent == null ? "null" : geoTrackingIntent));

        if (geoTrackingIntent == null) {
            String alarm = Context.ALARM_SERVICE;
            AlarmManager am = (AlarmManager) getSystemService(alarm);

            int type = AlarmManager.ELAPSED_REALTIME_WAKEUP;
            long interval = 60000;
            long triggerTime = SystemClock.elapsedRealtime() + interval;
            am.setInexactRepeating(type, triggerTime, interval, getGeoTrackingIntent());
        }
    }

    void cancelGEOAlarm() {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner in cancelGEOAlarm: " + (geoTrackingIntent == null ? "null" : geoTrackingIntent));

        String alarm = Context.ALARM_SERVICE;
        AlarmManager am = (AlarmManager) getSystemService(alarm);
        am.cancel(getGeoTrackingIntent());
        geoTrackingIntent = null;
    }

    void setupGPSTracking() {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner in setupGPSTracking locationRequest: " + (locationRequest == null ? "null" : locationRequest));

        if (locationRequest == null) {
            checkGeoPermission();
            // Create the LocationRequest object
            locationRequest = LocationRequest.create();
            // Use high accuracy
            locationRequest.setPriority(
                    LocationRequest.PRIORITY_BALANCED_POWER_ACCURACY);
            // Set the update interval to x seconds
            locationRequest.setInterval(UPDATE_INTERVAL);
            // Set the fastest update interval to x seconds
            locationRequest.setFastestInterval(FASTEST_INTERVAL);
        }
    }

    private void setupLoginUI() {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": setup login auth = " + (auth == null ? "null" : auth.getUserId()));

        auth = null;
        lastCallout = null;
        mapMarkers = new ArrayList<>();

// http://developer.android.com/google/gcm/adv.html#unreg-why        
//        if(gcm != null) {
//        	try {
//				gcm.unregister();
//			} 
//        	catch (IOException e) {
//				// TODO Auto-generated catch block
//				//e.printStackTrace();
//				Log.i(Utils.TAG, "GCM could not unregister: " + e.getMessage());
//			}
//        }
//        gcm = null;

        mDisplay.setText("");
        scrollToBottom(mDisplayScroll, mDisplay);

        TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
        txtMsg.setText(getResources().getString(R.string.login_credentials));

        Button btnLogin = (Button) findViewById(R.id.btnLogin);
        btnLogin.setEnabled(true);
        btnLogin.setVisibility(View.VISIBLE);
        if (logout_menu != null) logout_menu.setEnabled(isLoggedIn());

        Button btnMap = (Button) findViewById(R.id.btnMap);
        btnMap.setEnabled(false);
        btnMap.setVisibility(View.INVISIBLE);

        Button btnRespond = (Button) findViewById(R.id.btnRespond);
        btnRespond.setEnabled(false);
        btnRespond.setVisibility(View.INVISIBLE);

        Spinner spinRespond = (Spinner) findViewById(R.id.spinRespond);
        spinRespond.setEnabled(false);
        spinRespond.setVisibility(View.INVISIBLE);

        Button btnCallDetails = (Button) findViewById(R.id.btnCallDetails);
        btnCallDetails.setEnabled(false);
        btnCallDetails.setVisibility(View.INVISIBLE);

        Button btnCompleteCall = (Button) findViewById(R.id.btnCompleteCall);
        btnCompleteCall.setEnabled(false);
        btnCompleteCall.setVisibility(View.INVISIBLE);

        Button btnCancelCall = (Button) findViewById(R.id.btnCancelCall);
        btnCancelCall.setEnabled(false);
        btnCancelCall.setVisibility(View.INVISIBLE);

        EditText etFhid = (EditText) findViewById(R.id.etFhid);
        etFhid.setText(getResources().getString(R.string.firehallid));
        etFhid.setVisibility(View.VISIBLE);

        EditText etUid = (EditText) findViewById(R.id.etUid);
        etUid.setText(getResources().getString(R.string.userid));
        etUid.setVisibility(View.VISIBLE);

        EditText etUpw = (EditText) findViewById(R.id.etUpw);
        etUpw.setText("");
        etUpw.setVisibility(View.VISIBLE);

        hideFragment(R.id.map);

        kmlData = null;
    }

    private void setupCalloutUI(String respondingUserId) {
        Button btnMap = (Button) findViewById(R.id.btnMap);
        btnMap.setEnabled(false);
        btnMap.setVisibility(View.VISIBLE);

        Button btnRespond = (Button) findViewById(R.id.btnRespond);
        btnRespond.setVisibility(View.VISIBLE);
        btnRespond.setEnabled(false);

        Spinner spinRespond = (Spinner) findViewById(R.id.spinRespond);
        spinRespond.setVisibility(View.VISIBLE);
        spinRespond.setEnabled(true);

        Button btnCallDetails = (Button) findViewById(R.id.btnCallDetails);
        btnCallDetails.setEnabled(false);
        btnCallDetails.setVisibility(View.VISIBLE);

        Button btnCompleteCall = (Button) findViewById(R.id.btnCompleteCall);
        btnCompleteCall.setEnabled(false);
        btnCompleteCall.setVisibility(View.VISIBLE);

        Button btnCancelCall = (Button) findViewById(R.id.btnCancelCall);
        btnCancelCall.setEnabled(false);
        btnCancelCall.setVisibility(View.VISIBLE);

        if (lastCallout != null &&
                !FireHallCalloutStatus.isComplete(lastCallout.getStatus(),getConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class))) {

            btnCallDetails.setEnabled(true);
            btnCompleteCall.setEnabled(true);
            btnCancelCall.setEnabled(true);

            //if (respondingUserId == null || respondingUserId.isEmpty()) {
                btnRespond.setEnabled(true);
                spinRespond.setEnabled(true);
            //}
            showFragment(R.id.map);
        } else {
            if (lastCallout != null &&
                    FireHallCalloutStatus.isComplete(lastCallout.getStatus(),getConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class))) {

                TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                txtMsg.setText(getResources().getString(R.string.waiting_for_callout));
            }
            hideFragment(R.id.map);
        }

        if (lastCallout != null) {
            btnMap.setEnabled(true);
        }
    }

    @Override
    protected void onStart() {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner in onStart mLocationClient: " + (googleApiClient == null ? "null" : googleApiClient));

        // Connect the client.
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner in onStart calling googleApiClient.connect()");
        if (googleApiClient != null) {
            googleApiClient.connect();
        }
        super.onStart();
        // ATTENTION: This was auto-generated to implement the App Indexing API.
        // See https://g.co/AppIndexing/AndroidStudio for more information.
        client.connect();
        setupGPSTracking();
        // ATTENTION: This was auto-generated to implement the App Indexing API.
        // See https://g.co/AppIndexing/AndroidStudio for more information.
        Action viewAction = Action.newAction(
                Action.TYPE_VIEW, // TODO: choose an action type.
                "AppMain Page", // TODO: Define a title for the content shown.
                // TODO: If you have web page content that matches this app activity's content,
                // make sure this auto-generated web page URL is correct.
                // Otherwise, set the URL to null.
                Uri.parse("http://host/path"),
                // TODO: Make sure this auto-generated app URL is correct.
                Uri.parse("android-app://com.vejvoda.android.riprunner/http/host/path")
        );
        AppIndex.AppIndexApi.start(client, viewAction);
    }

    /*
     * Called when the Activity is no longer visible at all.
     * Stop updates and disconnect.
     */
    @Override
    protected void onStop() {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner in onStop: " + (googleApiClient == null ? "null" : googleApiClient));
        super.onStop();
        // ATTENTION: This was auto-generated to implement the App Indexing API.
        // See https://g.co/AppIndexing/AndroidStudio for more information.
        Action viewAction = Action.newAction(
                Action.TYPE_VIEW, // TODO: choose an action type.
                "AppMain Page", // TODO: Define a title for the content shown.
                // TODO: If you have web page content that matches this app activity's content,
                // make sure this auto-generated web page URL is correct.
                // Otherwise, set the URL to null.
                Uri.parse("http://host/path"),
                // TODO: Make sure this auto-generated app URL is correct.
                Uri.parse("android-app://com.vejvoda.android.riprunner/http/host/path")
        );
        AppIndex.AppIndexApi.end(client, viewAction);
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner in onStop calling googleApiClient.disconnect()");
        if (googleApiClient != null && googleApiClient.isConnected()) {
            googleApiClient.disconnect();
        }
        // ATTENTION: This was auto-generated to implement the App Indexing API.
        // See https://g.co/AppIndexing/AndroidStudio for more information.
        client.disconnect();
    }


    /**
     * Check the device to make sure it has the Google Play Services APK. If
     * it doesn't, display a dialog that allows users to download the APK from
     * the Google Play Store or enable it in the device's system settings.
     */
    private boolean checkPlayServices() {
        GoogleApiAvailability googleAPI = GoogleApiAvailability.getInstance();
        int resultCode = googleAPI.isGooglePlayServicesAvailable(this);
        if (resultCode != ConnectionResult.SUCCESS) {
            if (googleAPI.isUserResolvableError(resultCode)) {
                googleAPI.getErrorDialog(this, resultCode,
                        AppConstants.PLAY_SERVICES_RESOLUTION_REQUEST).show();
            } else {
                Log.i(Utils.TAG, Utils.getLineNumber() + ": This device is not supported.");
                finish();
            }
            return false;
        } else {
            if (googleApiClient == null) {
                checkGeoPermission();
                googleApiClient = new GoogleApiClient.Builder(this)
                        .addConnectionCallbacks(this)
                        .addOnConnectionFailedListener(this)
                        .addApi(LocationServices.API)
                        .build();
            }
        }
        return true;
    }

    /**
     * Stores the registration ID and the app versionCode in the application's
     * {@code SharedPreferences}.
     *
     * @param context application's context.
     * @param keyName the keyName of the config entity
     * @param value   the value of the config entity
     */
    private void storeConfigItem(Context context, String keyName, Object value) {
        SharedPreferences prefs = getGcmPreferences();
        int appVersion = getAppVersion(context);

        Log.i(Utils.TAG, Utils.getLineNumber() + ": Saving " + keyName + " on app version " + appVersion);

        SharedPreferences.Editor editor = prefs.edit();
        if (value instanceof String) {
            editor.putString(keyName, (String) value);
        } else if (value instanceof Boolean) {
            editor.putBoolean(keyName, (Boolean) value);
        }
        editor.putInt(AppConstants.PROPERTY_APP_VERSION, appVersion);
        editor.apply();
    }

    private <T> boolean hasConfigItem(Context context, String keyName, Class<T> type) {
        SharedPreferences prefs = getGcmPreferences();
        //String value = prefs.getString(keyName, "");
        T value = null;
        if (type.equals(String.class)) {
            value = type.cast(prefs.getString(keyName, ""));
        } else if (type.equals(Boolean.class)) {
            value = type.cast(prefs.getBoolean(keyName, true));
        }

        if (value == null) {
            return false;
        }
        // Check if app was updated; if so, it must clear the registration ID
        // since the existing regID is not guaranteed to work with the new
        // app version.
        int registeredVersion = prefs.getInt(AppConstants.PROPERTY_APP_VERSION, Integer.MIN_VALUE);
        int currentVersion = getAppVersion(context);
        return (registeredVersion == currentVersion);
    }

    /**
     * Gets the current registration ID for application on GCM service, if there is one.
     * <p/>
     * If result is empty, the app needs to register.
     *
     * @return registration ID, or empty string if there is no existing
     * registration ID.
     */
    <T> T getConfigItem(Context context, String keyName, Class<T> type) {
        final SharedPreferences prefs = getGcmPreferences();

        //String value = prefs.getString(keyName, "");
        T value = null;
        if (type.equals(String.class)) {
            value = type.cast(prefs.getString(keyName, ""));
        } else if (type.equals(Boolean.class)) {
            value = type.cast(prefs.getBoolean(keyName, true));
        }

        if (value == null) {
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Config Item not found: " + keyName);
            try {
                return type.newInstance();
            } catch (InstantiationException | IllegalAccessException e) {
                e.printStackTrace();
                Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            }
        }
        // Check if app was updated; if so, it must clear the registration ID
        // since the existing regID is not guaranteed to work with the new
        // app version.
        int registeredVersion = prefs.getInt(AppConstants.PROPERTY_APP_VERSION, Integer.MIN_VALUE);
        int currentVersion = getAppVersion(context);
        if (registeredVersion != currentVersion) {
            Log.i(Utils.TAG, Utils.getLineNumber() + ": App version changed.");
            try {
                return type.newInstance();
            } catch (InstantiationException | IllegalAccessException e) {
                Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            }
        }
        return value;
    }

    private String getGcmDeviceRegistrationId(boolean forceNewId) throws IOException {
        //if (gcm == null) {
        //    gcm = GoogleCloudMessaging.getInstance(context);
        //}

        String regid = getConfigItem(context, AppConstants.PROPERTY_REG_ID, String.class);
        if (forceNewId || regid.isEmpty()) {
            //String senderId = getConfigItem(context,AppConstants.PROPERTY_SENDER_ID,String.class);
            //regid = gcm.register(senderId);

            // Persist the regID - no need to register again.
            //storeConfigItem(context, AppConstants.PROPERTY_REG_ID, regid);
            setupGCMRegistration(true);
        }
        return regid;
    }

    private class RegisterBackgroundAsyncTask extends AsyncTask<Void, Void, String> {

        private String firehallId;
        private String userName;
        private String userPassword;

        public RegisterBackgroundAsyncTask(String firehallId, String userName, String userPassword) {
            super();
            this.firehallId = firehallId;
            this.userName = userName;
            this.userPassword = userPassword;
        }

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
            String msg;
            try {
                String regid = getGcmDeviceRegistrationId(false);
                auth = new FireHallAuthentication(
                        getConfigItem(context, AppConstants.PROPERTY_WEBSITE_URL, String.class),
                        firehallId, userName, userPassword, regid, false);
                msg = getResources().getString(R.string.waiting_for_callout);
                // You should send the registration ID to your server over HTTP, so it
                // can use GCM/HTTP or CCS to send messages to your app.
                sendRegistrationIdToBackend(auth);
            } catch (IOException ex) {
                msg = "Error :" + ex.getMessage();
                // If there is an error, don't just keep trying to register.
                // Require the user to click a button again, or perform
                // exponential back-off.
                Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", ex);
            }
            return msg;
        }

        @Override
        protected void onPostExecute(final String msg) {
            runOnUiThread(new Runnable() {
                public void run() {
                    mDisplay.append(msg + "\n");
                    scrollToBottom(mDisplayScroll, mDisplay);
                    getProgressDialog().hide();
                }
            });
        }
    }

    /**
     * Registers the application with GCM servers asynchronously.
     * <p/>
     * Stores the registration ID and the app versionCode in the application's
     * shared preferences.
     */
    private void registerInBackground() {
        EditText etFhid = (EditText) findViewById(R.id.etFhid);
        EditText etUid = (EditText) findViewById(R.id.etUid);
        EditText etUpw = (EditText) findViewById(R.id.etUpw);
        new RegisterBackgroundAsyncTask(etFhid.getText().toString(),
                etUid.getText().toString(),
                etUpw.getText().toString()).execute(null, null, null);
    }

    /**
     * Registers the application with GCM servers asynchronously.
     * <p/>
     * Stores the registration ID and the app versionCode in the application's
     * shared preferences.
     */
    private void respondInBackground(final int statusType) {
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
                    sendResponseToBackend(auth, statusType);
                } catch (IOException ex) {
                    msg = "Error :" + ex.getMessage();
                    // If there is an error, don't just keep trying to register.
                    // Require the user to click a button again, or perform
                    // exponential back-off.
                    Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error statusType" + statusType, ex);
                }
                return msg;
            }

            @Override
            protected void onPostExecute(final String msg) {
                runOnUiThread(new Runnable() {
                    public void run() {
                        mDisplay.append(msg + "\n");
                        mDisplay.setMovementMethod(new ScrollingMovementMethod());
                        getProgressDialog().hide();
                    }
                });

            }
        }.execute(null, null, null);
    }

    // Handle onclick events
    public void onClick(final View view) {

        if (view == findViewById(R.id.btnLogin)) {
            handleLoginClick();
        } else if (view == findViewById(R.id.btnMap)) {
            handleCalloutMapView();
        } else if (view == findViewById(R.id.btnRespond)) {
            handleRespondClick();
        } else if (view == findViewById(R.id.btnCompleteCall)) {
            handleCompleteCallClick();
        } else if (view == findViewById(R.id.btnCancelCall)) {
            handleCancelCallClick();
        } else if (view == findViewById(R.id.btnCallDetails)) {
            handleCalloutDetailsView();
        }
    }

    void handleCancelCallClick() {
        new AlertDialog.Builder(this)
                .setTitle(R.string.dialog_title_question)
                .setMessage(R.string.dialog_text_cancel_call)
                .setIcon(android.R.drawable.ic_dialog_alert)
                .setPositiveButton(android.R.string.yes,
                        new DialogInterface.OnClickListener() {

                            public void onClick(DialogInterface dialog, int whichButton) {
                                new AsyncTask<Void, Void, String>() {
                                    @Override
                                    protected String doInBackground(Void... params) {
                                        if (checkPlayServices()) {
                                            if (isLoggedIn()) {
                                                String jsonStatusDef = getConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class);
                                                respondInBackground(FireHallCalloutStatus.getResponseStatusIdForCancelled(jsonStatusDef));
                                            }
                                        } else {
                                            Log.i(Utils.TAG, Utils.getLineNumber() + ": No valid Google Play Services APK found.");
                                        }
                                        return "";
                                    }

                                    @Override
                                    protected void onPostExecute(String msg) {
                                        mDisplay.append(msg + "\n");
                                        scrollToBottom(mDisplayScroll, mDisplay);
                                    }
                                }.execute(null, null, null);
                            }
                        })
                .setNegativeButton(android.R.string.no, null).show();
    }

    void handleCompleteCallClick() {
        new AlertDialog.Builder(this)
                .setTitle(R.string.dialog_title_question)
                .setMessage(R.string.dialog_text_complete_call)
                .setIcon(android.R.drawable.ic_dialog_alert)
                .setPositiveButton(android.R.string.yes,
                        new DialogInterface.OnClickListener() {

                            public void onClick(DialogInterface dialog, int whichButton) {
                                new AsyncTask<Void, Void, String>() {
                                    @Override
                                    protected String doInBackground(Void... params) {
                                        if (checkPlayServices()) {
                                            if (isLoggedIn()) {
                                                String jsonStatusDef = getConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class);
                                                respondInBackground(FireHallCalloutStatus.getResponseStatusIdForCompleted(jsonStatusDef));
                                            }
                                        } else {
                                            Log.i(Utils.TAG, Utils.getLineNumber() + ": No valid Google Play Services APK found.");
                                        }
                                        return "";
                                    }

                                    @Override
                                    protected void onPostExecute(String msg) {
                                        mDisplay.append(msg + "\n");
                                        scrollToBottom(mDisplayScroll, mDisplay);
                                    }
                                }.execute(null, null, null);
                            }
                        })
                .setNegativeButton(android.R.string.no, null).show();
    }

    private int getResponseStatusIdForName(String name) {
        int result = -1;
        String jsonStatusDef = getConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class);
        try {
            return FireHallCalloutStatus.getResponseStatusIdForName(name,jsonStatusDef);
        }
        catch (JSONException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": *****Error***** - parsing status JSON",e);
            Toast.makeText(this, "Error - parsing status JSON, msg: " + e.getMessage(), Toast.LENGTH_SHORT).show();
        }
        return result;
    }
    void handleRespondClick() {
        Spinner spinRespond = (Spinner) findViewById(R.id.spinRespond);
        String statusName = (String)spinRespond.getSelectedItem();
        final int statusId = getResponseStatusIdForName(statusName);

        new AsyncTask<Void, Void, String>() {
            @Override
            protected String doInBackground(Void... params) {
                if (checkPlayServices()) {
                    if (isLoggedIn()) {
                        respondInBackground(statusId);
                    }
                } else {
                    Log.i(Utils.TAG, Utils.getLineNumber() + ": No valid Google Play Services APK found.");
                }
                return "";
            }

            @Override
            protected void onPostExecute(String msg) {
                mDisplay.append(msg + "\n");
                scrollToBottom(mDisplayScroll, mDisplay);
            }
        }.execute(null, null, null);
    }

    void handleLoginClick() {
        new AsyncTask<Void, Void, String>() {
            @Override
            protected String doInBackground(Void... params) {
                if (checkPlayServices()) {
                    if (!isLoggedIn()) {
                        registerInBackground();
                    }
                } else {
                    Log.i(Utils.TAG, Utils.getLineNumber() + ": No valid Google Play Services APK found.");
                }
                return "";
            }

            @Override
            protected void onPostExecute(final String msg) {
                runOnUiThread(new Runnable() {
                    public void run() {
                        mDisplay.append(msg + "\n");
                        scrollToBottom(mDisplayScroll, mDisplay);
                    }
                });
            }
        }.execute(null, null, null);
    }

    private void handleCalloutMapView() {
        try {
            String uri = String.format(Locale.ENGLISH, "http://maps.google.com/maps?daddr=%s,%s (%s)",
                    URLEncoder.encode(lastCallout.getGPSLat(), "utf-8"),
                    URLEncoder.encode(lastCallout.getGPSLong(), "utf-8"),
                    URLEncoder.encode(lastCallout.getMapAddress(), "utf-8"));
            Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
            intent.setClassName("com.google.android.apps.maps", "com.google.android.maps.MapsActivity");
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);

            if (intent.resolveActivity(getPackageManager()) != null) {
                context.startActivity(intent);
            } else {
                uri = String.format(Locale.ENGLISH, "http://maps.google.com/maps?&daddr=%s,%s (%s)",
                        URLEncoder.encode(lastCallout.getGPSLat(), "utf-8"),
                        URLEncoder.encode(lastCallout.getGPSLong(), "utf-8"),
                        URLEncoder.encode(lastCallout.getMapAddress(), "utf-8"));
                intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                try {
                    context.startActivity(intent);
                } catch (ActivityNotFoundException innerEx) {
                    Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", innerEx);
                    Toast.makeText(this, "Please install a maps application", Toast.LENGTH_LONG).show();
                }
            }
        } catch (UnsupportedEncodingException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            Toast.makeText(this, "UnsupportedEncodingException: " + e.getMessage(), Toast.LENGTH_LONG).show();
        }
    }

    private void handleCalloutDetailsView() {
        try {
            String uri = auth.getHostURL() +
                    getConfigItem(context, AppConstants.PROPERTY_CALLOUT_PAGE_URI, String.class) +
                    "?cid=" + URLEncoder.encode(lastCallout.getCalloutId(), "utf-8") +
                    "&fhid=" + URLEncoder.encode(auth.getFirehallId(), "utf-8") +
                    "&ckid=" + URLEncoder.encode(lastCallout.getCalloutKeyId(), "utf-8") +
                    "&member_id=" + URLEncoder.encode(auth.getUserId(), "utf-8");
            Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
            context.startActivity(intent);
        } catch (UnsupportedEncodingException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            Toast.makeText(this, "UnsupportedEncodingException: " + e.getMessage(), Toast.LENGTH_LONG).show();
        }
    }

    @Override
    protected void onDestroy() {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": destroying Rip Runner.");

        LocalBroadcastManager.getInstance(this).unregisterReceiver(getBroadCastReceiver());
        unregisterReceiver(activityReceiver);
        super.onDestroy();
    }

    /**
     * @return Application's version code from the {@code PackageManager}.
     */
    private static int getAppVersion(Context context) {
        try {
            PackageInfo packageInfo = context.getPackageManager()
                    .getPackageInfo(context.getPackageName(), 0);
            return packageInfo.versionCode;
        } catch (NameNotFoundException e) {
            // should never happen
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw new RuntimeException("Could not get package name: " + e);
        }
    }

    /**
     * @return Application's {@code SharedPreferences}.
     */
    private SharedPreferences getGcmPreferences() {
        return getSharedPreferences(AppMainActivity.class.getSimpleName(),
                Context.MODE_PRIVATE);
    }

    private boolean isGcmErrorNotRegistered(String responseString) {
        //|GCM_ERROR:
        return (responseString != null && responseString.contains("|GCM_ERROR:"));
    }

    //GCM_ERROR:MismatchSenderId
    private boolean isGcmErrorBadSenderId(String responseString) {
        //|GCM_ERROR:
        return (responseString != null && responseString.contains("|GCM_ERROR:MismatchSenderId"));
    }

    /**
     * Sends the registration ID to your server over HTTP, so it can use GCM/HTTP or CCS to send
     * messages to your app. Not needed for this demo since the device sends upstream messages
     * to a server that echoes back the message using the 'from' address in the message.
     *
     * @throws IOException
     */
    private void sendRegistrationIdToBackend(FireHallAuthentication auth) throws IOException {

        Map<String, String> params = new HashMap<>();
        params.put("rid", auth.getGCMRegistrationId());
        params.put("fhid", auth.getFirehallId());
        params.put("uid", auth.getUserId());
        params.put("upwd", auth.getUserPassword());
        String paramString = Utils.getURLParamString(params);
        String URL = auth.getHostURL() +
                getConfigItem(context, AppConstants.PROPERTY_LOGIN_PAGE_URI, String.class) +
                paramString;

        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner about to call url: [" + URL + "]");

        HttpURLConnection urlConnection = Utils.openHttpConnection(URL, "GET");
        int code = urlConnection.getResponseCode();
        if (code == HttpURLConnection.HTTP_OK) {
            final String responseString = Utils.getUrlConnectionResultSring(urlConnection).trim();
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner response for register_device: " + responseString);

            if (isGcmErrorBadSenderId(responseString)) {
                if (gcmLoginErrorCount == 0) {
                    gcmLoginErrorCount++;

                    String regid = getGcmDeviceRegistrationId(true);
                    auth.setGCMRegistrationId(regid);
                    sendRegistrationIdToBackend(auth);
                } else {
                    gcmLoginErrorCount = 0;
                    runOnUiThread(new Runnable() {
                        public void run() {
                            EditText etUpw = (EditText) findViewById(R.id.etUpw);
                            etUpw.setText("");

                            TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                            txtMsg.setText(R.string.invalid_senderid);

                            getProgressDialog().hide();
                        }
                    });
                }
            } else if (isGcmErrorNotRegistered(responseString)) {
                if (gcmLoginErrorCount == 0) {
                    gcmLoginErrorCount++;

                    String regid = getGcmDeviceRegistrationId(true);
                    auth.setGCMRegistrationId(regid);
                    sendRegistrationIdToBackend(auth);
                } else {
                    gcmLoginErrorCount = 0;
                    runOnUiThread(new Runnable() {
                        public void run() {
                            EditText etUpw = (EditText) findViewById(R.id.etUpw);
                            etUpw.setText("");

                            TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                            txtMsg.setText(getString(R.string.gcm_device_error, responseString));

                            getProgressDialog().hide();
                        }
                    });
                }
            } else {
                if (responseString.startsWith("OK=")) {
                    String[] responseParts = responseString.split("\\|");
                    if (responseParts.length > 2) {
                        String firehallCoords = responseParts[2];
                        String[] firehallCoordsParts = firehallCoords.split(",");
                        if (firehallCoordsParts.length == 2) {
                            auth.setFireHallGeoLatitude(firehallCoordsParts[0]);
                            auth.setFireHallGeoLongitude(firehallCoordsParts[1]);
                        }
                    }

                    handleRegistrationSuccess(auth);
                } else {
                    runOnUiThread(new Runnable() {
                        public void run() {
                            EditText etUpw = (EditText) findViewById(R.id.etUpw);
                            etUpw.setText("");

                            TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                            txtMsg.setText(getString(R.string.invalid_login_attempt, responseString));

                            getProgressDialog().hide();
                        }
                    });
                }
            }
        } else {
            //Closes the connection.
            //urlConnection.close();

            final String errorText = Utils.getUrlConnectionErorResultSring(urlConnection);
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner ERROR response for register_device, code: " + code + " msg: " + errorText);

            runOnUiThread(new Runnable() {
                public void run() {
                    EditText etUpw = (EditText) findViewById(R.id.etUpw);
                    etUpw.setText("");

                    TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                    txtMsg.setText(getString(R.string.invalid_login_attempt, errorText));

                    getProgressDialog().hide();
                }
            });
        }
    }

    void handleRegistrationSuccess(FireHallAuthentication auth) {

        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner start handleRegistrationSuccess ...");

        storeConfigItem(context, AppConstants.PROPERTY_FIREHALL_ID, auth.getFirehallId());
        storeConfigItem(context, AppConstants.PROPERTY_USER_ID, auth.getUserId());

        auth.setRegisteredBackend(true);
        final String loggedOnUser = auth.getUserId();
        final String loggedOnUserFirehallId = auth.getFirehallId();

        runOnUiThread(new Runnable() {
            public void run() {

                Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner run handleRegistrationSuccess ...");

                TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                txtMsg.setText(getResources().getString(R.string.login_success_msg, loggedOnUser, loggedOnUserFirehallId));

                // Enable when debugging
                //mDisplay.setText(responseString);

                Button btnLogin = (Button) findViewById(R.id.btnLogin);
                btnLogin.setEnabled(false);
                btnLogin.setVisibility(View.GONE);
                if (logout_menu != null) logout_menu.setEnabled(isLoggedIn());

                EditText etFhid = (EditText) findViewById(R.id.etFhid);
                etFhid.setText("");
                etFhid.setVisibility(View.GONE);
                EditText etUid = (EditText) findViewById(R.id.etUid);
                etUid.setText("");
                etUid.setVisibility(View.GONE);
                EditText etUpw = (EditText) findViewById(R.id.etUpw);
                etUpw.setText("");
                etUpw.setVisibility(View.GONE);

                setupCalloutUI(null);

                playSound(context, FireHallSoundPlayer.SOUND_LOGIN);
                long pattern[] = {0, 200, 400, 200, 400, 200};
                vibrateAlert(context, pattern);

                getProgressDialog().hide();

                InputMethodManager imm = (InputMethodManager) getSystemService(
                        Context.INPUT_METHOD_SERVICE);
                imm.hideSoftInputFromWindow(etUpw.getWindowToken(), 0);
            }
        });
    }

    /**
     * Sends the registration ID to your server over HTTP, so it can use GCM/HTTP or CCS to send
     * messages to your app. Not needed for this demo since the device sends upstream messages
     * to a server that echoes back the message using the 'from' address in the message.
     *
     * @throws IOException
     */
    private void sendResponseToBackend(FireHallAuthentication auth,
                                       final int statusType) throws IOException {

        Map<String, String> params = new HashMap<>();
        params.put("cid", lastCallout.getCalloutId());
        params.put("ckid", lastCallout.getCalloutKeyId());
        params.put("fhid", auth.getFirehallId());
        params.put("uid", auth.getUserId());
        params.put("upwd", auth.getUserPassword());
        params.put("lat", String.valueOf(getLastGPSLatitude()));
        params.put("long", String.valueOf(getLastGPSLongitude()));
        params.put("member_id", auth.getUserId());
        params.put("status", String.valueOf(statusType));

        String paramString = Utils.getURLParamString(params);
        String URL = auth.getHostURL() +
                getConfigItem(context, AppConstants.PROPERTY_RESPOND_PAGE_URI, String.class) +
                paramString;


        HttpURLConnection urlConnection = Utils.openHttpConnection(URL, "GET");
        int code = urlConnection.getResponseCode();
        if (code == HttpURLConnection.HTTP_OK) {
            final String responseString = Utils.getUrlConnectionResultSring(urlConnection).trim();
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner response for cr: " + responseString);

            if (responseString.startsWith("OK=")) {
                handleResponseSuccess();
            } else {
                runOnUiThread(new Runnable() {
                    public void run() {
                        TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                        txtMsg.setText(getString(R.string.invalid_server_reply, "cr", responseString));
                        getProgressDialog().hide();
                    }
                });
            }
        } else {
            //Closes the connection.
            //response.getEntity().getContent().close();

            final String errorText = Utils.getUrlConnectionErorResultSring(urlConnection);
            Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner ERROR response for cr, code: " + code + " msg: " + errorText);

            runOnUiThread(new Runnable() {
                public void run() {
                    TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                    txtMsg.setText(getString(R.string.invalid_server_reply, "cr", (errorText != null ? errorText : "null")));
                    getProgressDialog().hide();
                }
            });
        }
    }

    void handleResponseSuccess() {
        runOnUiThread(new Runnable() {
            public void run() {

                Button btnRespond = (Button) findViewById(R.id.btnRespond);
                btnRespond.setVisibility(View.VISIBLE);
                btnRespond.setEnabled(true);

                Spinner spinRespond = (Spinner) findViewById(R.id.spinRespond);
                spinRespond.setVisibility(View.VISIBLE);
                spinRespond.setEnabled(true);

                TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                txtMsg.setText(getResources().getString(R.string.callout_respond_success));

                playSound(context, FireHallSoundPlayer.SOUND_DINGLING);
                long pattern[] = {0, 500, 500};
                vibrateAlert(context, pattern);

                getProgressDialog().hide();
            }
        });
    }

    /**
     * Sends the registration ID to your server over HTTP, so it can use GCM/HTTP or CCS to send
     * messages to your app. Not needed for this demo since the device sends upstream messages
     * to a server that echoes back the message using the 'from' address in the message.
     *
     * @throws UnsupportedEncodingException
     */
    public void sendGeoTrackingToBackend() throws UnsupportedEncodingException {
        String result = "";

        if (isLoggedIn() && lastCallout != null &&
                !FireHallCalloutStatus.isComplete(lastCallout.getStatus(),getConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class))) {

//    		runOnUiThread(new Runnable() {
//     		   public void run() {
//    		
//	    		Toast.makeText(context, "Tracking GEO Coordinates: " + 
//	    					String.valueOf(getLastGPSLatitude()) + "," + 
//	    					String.valueOf(getLastGPSLongitude()), Toast.LENGTH_LONG).show();
//    		   }
//    		});

            boolean track_geo_coords = (getLastGPSLatitude() != 0 && getLastGPSLongitude() != 0);
            //boolean track_geo_coords = true;
            if (track_geo_coords) {

                Map<String, String> params = new HashMap<>();
                params.put("fhid", auth.getFirehallId());
                params.put("cid", lastCallout.getCalloutId());
                params.put("uid", auth.getUserId());
                params.put("ckid", lastCallout.getCalloutKeyId());
                params.put("upwd", auth.getUserPassword());
                params.put("lat", String.valueOf(getLastGPSLatitude()));
                params.put("long", String.valueOf(getLastGPSLongitude()));

                String paramString = Utils.getURLParamString(params);
                String URL = auth.getHostURL() +
                        getConfigItem(context, AppConstants.PROPERTY_TRACKING_PAGE_URI, String.class) +
                        paramString;

                try {
                    HttpURLConnection urlConnection = Utils.openHttpConnection(URL, "GET");
                    int code = urlConnection.getResponseCode();
                    if (code == HttpURLConnection.HTTP_OK) {
                        final String responseString = Utils.getUrlConnectionResultSring(urlConnection).trim();
                        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner response for ct: " + responseString);

                        if (responseString.startsWith("OK=")) {
                            extractCalloutResponders(responseString);

//			        		runOnUiThread(new Runnable() {
//			        		   public void run() {
//			
//			        			   Toast.makeText(context, "Success tracking GEO Coordinates now.", Toast.LENGTH_LONG).show();
//			        		   }
//			        		});

                            result = responseString;
                        } else if (responseString.startsWith("CALLOUT_ENDED=")) {

                            runOnUiThread(new Runnable() {
                                public void run() {
                                    Toast.makeText(context, "CALLOUT ENDED - GEO Coordinates check.", Toast.LENGTH_LONG).show();
                                }
                            });

                            result = responseString;
                        } else {
                            runOnUiThread(new Runnable() {
                                public void run() {
                                    TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                                    txtMsg.setText(getString(R.string.invalid_server_reply, "ct", responseString));
                                    //getProgressDialog().hide();
                                }
                            });

                            result = responseString;
                        }
                    } else {
                        //Closes the connection.
                        //response.getEntity().getContent().close();

                        final String errorText = Utils.getUrlConnectionErorResultSring(urlConnection);
                        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner ERROR response for ct, code: " + code + " msg: " + errorText);

                        runOnUiThread(new Runnable() {
                            public void run() {
                                TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                                txtMsg.setText(getString(R.string.invalid_server_reply, "ct", (errorText != null ? errorText : "null")));

                                //getProgressDialog().hide();
                            }
                        });

                        result = errorText;
                    }
                } catch (IOException e) {
                    Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);

                    final IOException ex = e;
                    runOnUiThread(new Runnable() {
                        public void run() {
                            Toast.makeText(context, "Error detected: " + ex.getMessage(), Toast.LENGTH_LONG).show();
                        }
                    });
                }
            }
        }

        if (!result.equals("") && result.startsWith("CALLOUT_ENDED=") &&
                lastCallout != null) {

            String jsonStatusDef = getConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, String.class);
            processCalloutResponseTrigger("Callout has ended!",
                    lastCallout.getCalloutId(),
                    String.valueOf(FireHallCalloutStatus.getResponseStatusIdForCompleted(jsonStatusDef)),
                    null);
        }
    }

    private void extractCalloutResponders(final String responseString) {
        if (this.lastCallout != null) {
            this.lastCallout.clearResponders();

            String[] responseParts = responseString.split("\\|");
            if (responseParts.length >= 2) {
                String responders = responseParts[1];
                String[] respondersList = responders.split("\\^");
                if (respondersList.length > 0) {
                    Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner adding responder count: " + respondersList.length);

                    for (String responder : respondersList) {
                        String[] responderParts = responder.split(",");
                        if (responseParts.length >= 3) {
                            Responder respondingPerson =
                                    this.lastCallout.new Responder(
                                            responderParts[0],
                                            responderParts[1],
                                            responderParts[2]);
                            this.lastCallout.addResponder(respondingPerson);
                        }
                    }
                }
            }
        }
    }

    public void processCalloutResponseTrigger(final String calloutMsg,
                                              String callout_id, String callout_status, final String response_userId) {

        Log.i(Utils.TAG, Utils.getLineNumber() +
                ": RipRunner -> process response, lastcallout = " +
                (lastCallout == null ? "null" : lastCallout.toString()));

        if (lastCallout != null) {
            if (lastCallout.getCalloutId().equals(callout_id)) {
                if (!lastCallout.getStatus().equals(callout_status)) {
                    lastCallout.setStatus(callout_status);
                }
            }
        }
        runOnUiThread(new Runnable() {
            public void run() {
                mDisplay = (TextView) findViewById(R.id.display);
                mDisplay.append("\n" + calloutMsg);
                scrollToBottom(mDisplayScroll, mDisplay);

                setupCalloutUI(response_userId);

                playSound(context, FireHallSoundPlayer.SOUND_DINGLING);
                long pattern[] = {0, 500, 500};
                vibrateAlert(context, pattern);
            }
        });
    }

    public void processAdminMsgTrigger(final String adminMsg) {
        runOnUiThread(new Runnable() {
            public void run() {
                mDisplay = (TextView) findViewById(R.id.display);
                mDisplay.append("\n" + getResources().getString(R.string.Message_from_admin_prefix) + adminMsg);
                scrollToBottom(mDisplayScroll, mDisplay);

                Uri notification = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION);
                Ringtone r = RingtoneManager.getRingtone(getApplicationContext(), notification);
                r.play();
            }
        });
    }

    public void processDeviceMsgTrigger(final String deviceMsg) {
        runOnUiThread(new Runnable() {
            public void run() {
                mDisplay = (TextView) findViewById(R.id.display);
                mDisplay.append("\n" + deviceMsg);
                scrollToBottom(mDisplayScroll, mDisplay);
            }
        });
    }

    public void processCalloutTrigger(String callOutId, String callKeyId,
                                      String callType, String gpsLatStr, String gpsLongStr, String callAddress,
                                      String callMapAddress, String callOutUnits, String callOutStatus,
                                      final String calloutMsg) {
        lastCallout = new FireHallCallout(
                callOutId, callKeyId, callType, gpsLatStr, gpsLongStr, callAddress,
                callMapAddress, callOutUnits, callOutStatus);
        mapMarkers = new ArrayList<>();

        runOnUiThread(new Runnable() {
            public void run() {

                mDisplay = (TextView) findViewById(R.id.display);
                mDisplay.setText(calloutMsg);
                scrollToBottom(mDisplayScroll, mDisplay);

                playSound(context, FireHallSoundPlayer.SOUND_PAGER_TONE_PG);
                long pattern[] = {0, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500};
                vibrateAlert(context, pattern);

                setupCalloutUI(null);
            }
        });
    }

    ProgressDialog getProgressDialog() {
        if (loadingDlg == null) {
            loadingDlg = new ProgressDialog(AppMainActivity.this);
        }
        return loadingDlg;
    }

    void showProgressDialog(boolean show, String msg) {
        if (show) {
            getProgressDialog().setMessage(msg);
            getProgressDialog().setProgressStyle(ProgressDialog.STYLE_SPINNER);
            getProgressDialog().setIndeterminate(true);
            getProgressDialog().setCancelable(false);
            getProgressDialog().show();
        } else {
            getProgressDialog().hide();
        }
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        // Inflate the menu items for use in the action bar
        MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.main_activity_actions, menu);
        logout_menu = menu.findItem(R.id.action_logout);
        return super.onCreateOptionsMenu(menu);
    }

    @Override
    public boolean onPrepareOptionsMenu(Menu menu) {

        if (logout_menu != null)
            logout_menu.setEnabled(isLoggedIn()); // here pass the index of save menu item
        return super.onPrepareOptionsMenu(menu);
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        // Handle presses on the action bar items
        Log.i(Utils.TAG, Utils.getLineNumber() + ": handling selected option in Rip Runner.");

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
                openSettings(false);
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
            Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
            intent.setClassName("com.google.android.apps.maps", "com.google.android.maps.MapsActivity");
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);

            if (intent.resolveActivity(getPackageManager()) != null) {
                context.startActivity(intent);
            } else {
                uri = String.format(Locale.ENGLISH, "http://maps.google.com/maps?q=%s,%s",
                        URLEncoder.encode(String.valueOf(getLastGPSLatitude()), "utf-8"),
                        URLEncoder.encode(String.valueOf(getLastGPSLongitude()), "utf-8"));
                intent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                try {
                    context.startActivity(intent);
                } catch (ActivityNotFoundException innerEx) {
                    Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", innerEx);
                    Toast.makeText(this, "Please install a maps application", Toast.LENGTH_LONG).show();
                }
            }
        } catch (UnsupportedEncodingException e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            Toast.makeText(this, "UnsupportedEncodingException: " +
                    e.getMessage(), Toast.LENGTH_LONG).show();
        }
    }

    private boolean isLoggedIn() {
        return (auth != null && auth.getRegisteredBackend());
    }

    private void logout() {
        if (checkPlayServices()) {
            if (auth != null) {
                Log.i(Utils.TAG, Utils.getLineNumber() + ": Logging out of Rip Runner.");

                runOnUiThread(new Runnable() {
                    public void run() {
                        setupLoginUI();
                        playSound(context, FireHallSoundPlayer.SOUND_DINGLING);
                        long pattern[] = {0, 500};
                        vibrateAlert(context, pattern);

                    }
                });
            }
        } else {
            Log.i(Utils.TAG, Utils.getLineNumber() + ": No valid Google Play Services APK found.");
        }

        kmlData = null;
    }

    private void clearUI() {
        mDisplay.setText("");
        scrollToBottom(mDisplayScroll, mDisplay);
    }

    private void openSettings(boolean auto_update_settings) {
        cancelGEOAlarm();

        Intent intent = new Intent(getApplicationContext(), SettingsActivity.class);
        intent.setClass(AppMainActivity.this, SettingsActivity.class);
        intent.putExtra("com.vejvoda.android.riprunner.auto_update_settings", auto_update_settings);
        startActivityForResult(intent, 0);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        displayUserSettings();
    }

    private void displayUserSettings() {
        SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(this);

        String host_url = sharedPrefs.getString(AppConstants.PROPERTY_WEBSITE_URL, "");
        String sender_id = sharedPrefs.getString(AppConstants.PROPERTY_SENDER_ID, "");
        Boolean tracking_enabled = sharedPrefs.getBoolean(AppConstants.PROPERTY_TRACKING_ENABLED, true);

        storeConfigItem(context, AppConstants.PROPERTY_WEBSITE_URL, host_url);
        storeConfigItem(context, AppConstants.PROPERTY_SENDER_ID, sender_id);
        setupGCMRegistration(false);
        storeConfigItem(context, AppConstants.PROPERTY_TRACKING_ENABLED, tracking_enabled);

//	    String login_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_LOGIN_PAGE_URI, "register_device.php");
//	    String callout_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_CALLOUT_PAGE_URI, "ci.php");
//	    String respond_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_RESPOND_PAGE_URI, "cr.php");
//	    String tracking_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_TRACKING_PAGE_URI, "ct.php");
        String login_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_LOGIN_PAGE_URI, "mobile-login/");
        String callout_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_CALLOUT_PAGE_URI, "ci/");
        String respond_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_RESPOND_PAGE_URI, "cr/");
        String tracking_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_TRACKING_PAGE_URI, "ct/");

        String kml_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_KML_PAGE_URI, "");
        String android_errors_page_uri = sharedPrefs.getString(AppConstants.PROPERTY_ANDROID_ERROR_PAGE_URI, "");
        String status_list = sharedPrefs.getString(AppConstants.PROPERTY_STATUS_LIST, "");

        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner updating app URLs [" + login_page_uri + "]" +
                " [" + callout_page_uri + "]" + " [" + respond_page_uri + "]" + " [" + tracking_page_uri + "]" +
                " [" + kml_page_uri + "]" + " [" + android_errors_page_uri + "]");

        storeConfigItem(context, AppConstants.PROPERTY_LOGIN_PAGE_URI, login_page_uri);
        storeConfigItem(context, AppConstants.PROPERTY_CALLOUT_PAGE_URI, callout_page_uri);
        storeConfigItem(context, AppConstants.PROPERTY_RESPOND_PAGE_URI, respond_page_uri);
        storeConfigItem(context, AppConstants.PROPERTY_TRACKING_PAGE_URI, tracking_page_uri);
        storeConfigItem(context, AppConstants.PROPERTY_KML_PAGE_URI, kml_page_uri);
        storeConfigItem(context, AppConstants.PROPERTY_ANDROID_ERROR_PAGE_URI, android_errors_page_uri);
        storeConfigItem(context, AppConstants.PROPERTY_STATUS_LIST, status_list);

        setupResponseStatuses();

        startGEOAlarm();
    }

    private BroadcastReceiver getBroadCastReceiver() {
        if (broadcastReceiver == null) {
            broadcastReceiver = new AppMainBroadcastReceiver();
        }
        return broadcastReceiver;
    }

    /**
     * Populate the SoundPool
     */
    public static void initSounds(Context context) {
        //soundPool = new SoundPool(2, AudioManager.STREAM_MUSIC, 100);
        AudioAttributes attributes = new AudioAttributes.Builder()
                .setUsage(AudioAttributes.USAGE_MEDIA)
                .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                .build();
        soundPool = new SoundPool.Builder()
                .setAudioAttributes(attributes)
                .setMaxStreams(5)
                .build();

        soundPoolMap = new HashMap<>();

        soundPoolMap.put(FireHallSoundPlayer.SOUND_DINGLING, soundPool.load(context, R.raw.dingling, 1));
        soundPoolMap.put(FireHallSoundPlayer.SOUND_LOGIN, soundPool.load(context, R.raw.login, 1));
        soundPoolMap.put(FireHallSoundPlayer.SOUND_PAGE1, soundPool.load(context, R.raw.page1, 1));
        soundPoolMap.put(FireHallSoundPlayer.SOUND_PAGER_TONE_PG, soundPool.load(context, R.raw.pager_tone_pg, 1));
    }

    /**
     * Play a given sound in the soundPool
     */
    public static void playSound(Context context, int soundID) {
        if (soundPool == null || soundPoolMap == null) {
            initSounds(context);
        }
        //float volume = (float) 1.0; // whatever in the range = 0.0 to 1.0
        AudioManager audioManager = (AudioManager) context.getSystemService(AUDIO_SERVICE);
        // Current volumn Index of particular stream type.
        //float currentVolumeIndex = (float)audioManager.getStreamVolume(AudioManager.STREAM_MUSIC);
        // Get the maximum volume index for a particular stream type.
        //float maxVolumeIndex  = (float)audioManager.getStreamMaxVolume(AudioManager.STREAM_MUSIC);

        // Volumn (0 --> 1)
        //float volume = currentVolumeIndex / maxVolumeIndex;

        if (audioManager.getRingerMode() == AudioManager.RINGER_MODE_NORMAL) {
            // play sound with same right and left volume, with a priority of 1,
            // zero repeats (i.e play once), and a playback rate of 1f
            soundPool.play(soundPoolMap.get(soundID), 1, 1, 1, 0, 1f);
            //soundPool.play(soundPoolMap.get(soundID), 1, 1, 1, 0, 1f);
        }
    }

    public static void vibrateAlert(Context context, long pattern[]) {
        Vibrator vibrator = (Vibrator) context.getSystemService(Context.VIBRATOR_SERVICE);
        if (vibrator != null && vibrator.hasVibrator()) {
            // 2nd argument is for repetition pass -1 if you do not want to repeat the Vibrate
            vibrator.vibrate(pattern, -1);
        }
    }

    @Override
    public void onConnectionFailed(@NonNull ConnectionResult connectionResult) {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner GPS Connection FAILED: " + connectionResult.getErrorCode());
        Toast.makeText(this, "GPS Connection FAILED!", Toast.LENGTH_SHORT).show();
    }

    @Override
    public void onConnectionSuspended(int arg0) {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner GPS Connection SUSPENDED!");
        Toast.makeText(this, "GPS Connection SUSPENDED!", Toast.LENGTH_SHORT).show();

        if (googleApiClient != null) {
            googleApiClient.connect();
        }
    }

    @Override
    public void onConnected(Bundle connectionHint) {
        // Display the connection status
        Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner GPS Connected: " + (connectionHint == null ? "null" : connectionHint));
        Toast.makeText(this, "GPS Connected", Toast.LENGTH_SHORT).show();

        if (updatesRequested) {
            checkGeoPermission();
            setupGPSTracking();

            Location location = LocationServices.FusedLocationApi.getLastLocation(googleApiClient);
            if (location != null) {
                onLocationChanged(location);
            }
            LocationServices.FusedLocationApi.requestLocationUpdates(
                    googleApiClient, locationRequest, this);
        }
    }

    private void checkGeoPermission() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) !=
                PackageManager.PERMISSION_GRANTED) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": MainApp Requesting fine lcoation access.");
            // Check Permissions Now
            final int REQUEST_LOCATION = 2;
            ActivityCompat.requestPermissions(this, new String[]{Manifest.permission.ACCESS_FINE_LOCATION},
                    REQUEST_LOCATION);
        }
    }

    @Override
    protected void onPause() {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": pausing Rip Runner.");
        super.onPause();

        if (googleApiClient != null && googleApiClient.isConnected()) {
            LocationServices.FusedLocationApi.removeLocationUpdates(
                    googleApiClient, this);
        }
    }

    @Override
    protected void onResume() {
        Log.i(Utils.TAG, Utils.getLineNumber() + ": resuming Rip Runner.");

        super.onResume();

        registerReceiver(activityReceiver, Utils.getMainAppIntentFilter());
        // Check device for Play Services APK.
        checkPlayServices();
        if (googleApiClient != null && googleApiClient.isConnected() && updatesRequested) {
            checkGeoPermission();
            LocationServices.FusedLocationApi.requestLocationUpdates(
                    googleApiClient, locationRequest, this);

        }
    }

    @Override
    public void onLocationChanged(Location location) {
        try {
            lastTrackedGEOLocation = location;
            if (lastTrackedGEOLocation != null && inlineMap != null) {
                //Toast.makeText(this, "GPS location was found!", Toast.LENGTH_SHORT).show();

                //LatLng latLng = new LatLng(lastTrackedGEOLocation.getLatitude(),
                //							lastTrackedGEOLocation.getLongitude());
                //CameraUpdate cameraUpdate = CameraUpdateFactory.newLatLngZoom(latLng, 17);

                if (auth != null) {
                    if (isFragmentVisible(R.id.map)) {
                        if (mapMarkers == null || mapMarkers.isEmpty()) {
                            inlineMap.clear();
                        }
                        // Add current user location
                        MarkerOptions currentUserMarkerOptions = new MarkerOptions();
                        currentUserMarkerOptions.position(
                                new LatLng(lastTrackedGEOLocation.getLatitude(),
                                        lastTrackedGEOLocation.getLongitude()));
                        currentUserMarkerOptions.draggable(false);
                        currentUserMarkerOptions.title(auth.getUserId());
                        currentUserMarkerOptions.icon(BitmapDescriptorFactory.defaultMarker(BitmapDescriptorFactory.HUE_GREEN));
                        Marker newCurrentUserMarker = inlineMap.addMarker(currentUserMarkerOptions);
                        //newCurrentUserMarker.showInfoWindow();

                        //CameraPosition cp = null;
                        if (mapMarkers != null && mapMarkers.size() >= 3) {
                            //cp = inlineMap.getCameraPosition();
                            currentUserMarker.remove();
                            currentUserMarker = newCurrentUserMarker;
                        } else {
                            mapMarkers = new ArrayList<>();

                            currentUserMarker = newCurrentUserMarker;
                            mapMarkers.add(currentUserMarker);

                            // Add Callout Info to map
                            if (lastCallout != null) {
                                mapFragment.setMenuVisibility(true);
                            }
                            // Add callout location
                            addCalloutLocation();
                            // Add Firehall location
                            addFirehallLocation();
                            // Add responders
                            addResponders();

                            LatLngBounds bounds = getLatLngBounds();

                            addKmlDataLines();

                            int padding = 150; // offset from edges of the map in pixels
                            CameraUpdate cameraUpdate = CameraUpdateFactory.newLatLngBounds(bounds, padding);
                            inlineMap.moveCamera(cameraUpdate);
                            inlineMap.animateCamera(cameraUpdate);

                            //if (cp != null) {
                            //    inlineMap.moveCamera(CameraUpdateFactory.newCameraPosition(cp));
                            //}

                            setupKMLData();
                        }
                    }
                } else {
                    mapMarkers = new ArrayList<>();
                }
            }
        } catch (Exception e) {
            Log.e(Utils.TAG, Utils.getLineNumber() + ": ****** Rip Runner Error ******", e);
        }
    }

    private void addKmlDataLines() {
        if (kmlData != null && kmlData.getPathList() != null &&
                !kmlData.getPathList().isEmpty()) {
            Vector<Polyline> lines = new Vector<>();
            for (PolylineOptions line : kmlData.getPathList()) {
                lines.add(inlineMap.addPolyline(line));
            }

            for (Polyline line : lines) {
                line.setWidth(4);
                line.setColor(Color.RED);
                line.setGeodesic(true);
                line.setVisible(true);
            }
        }
    }

    @NonNull
    private LatLngBounds getLatLngBounds() {
        LatLngBounds.Builder builder = new LatLngBounds.Builder();
        for (Marker marker : mapMarkers) {
            builder.include(marker.getPosition());
        }
        return builder.build();
    }

    private void addResponders() {
        if (lastCallout != null) {
            for (Responder responder : lastCallout.getResponders()) {

                if (responder.getGPSLat() != null &&
                        !responder.getGPSLat().isEmpty() &&
                        responder.getGPSLong() != null &&
                        !responder.getGPSLong().isEmpty()) {

                    if (!auth.getUserId().equals(responder.getName())) {
                        // Add current user location
                        MarkerOptions responderMarkerOptions = new MarkerOptions();
                        responderMarkerOptions.position(
                                new LatLng(Double.valueOf(responder.getGPSLat()),
                                        Double.valueOf(responder.getGPSLong())));
                        responderMarkerOptions.draggable(false);
                        responderMarkerOptions.title(responder.getName());
                        responderMarkerOptions.icon(BitmapDescriptorFactory.defaultMarker(BitmapDescriptorFactory.HUE_GREEN));
                        Marker responderMarker = inlineMap.addMarker(responderMarkerOptions);
                        //currentUserMarker.showInfoWindow();
                        mapMarkers.add(responderMarker);
                    }
                }
            }
        }
    }

    private void addFirehallLocation() {
        if (auth.getFireHallGeoLatitude() != null &&
                !auth.getFireHallGeoLatitude().isEmpty() &&
                auth.getFireHallGeoLongitude() != null &&
                !auth.getFireHallGeoLongitude().isEmpty()) {

            MarkerOptions firehallMarkerOptions = new MarkerOptions();
            firehallMarkerOptions.position(
                    new LatLng(Double.valueOf(auth.getFireHallGeoLatitude()),
                            Double.valueOf(auth.getFireHallGeoLongitude())));
            firehallMarkerOptions.draggable(false);
            firehallMarkerOptions.title("Firehall");
            firehallMarkerOptions.icon(BitmapDescriptorFactory.defaultMarker(BitmapDescriptorFactory.HUE_BLUE));
            Marker firehallMarker = inlineMap.addMarker(firehallMarkerOptions);
            //firehallMarker.showInfoWindow();
            mapMarkers.add(firehallMarker);
        }
    }

    private void addCalloutLocation() {
        if (lastCallout != null && lastCallout.getGPSLat() != null &&
                !lastCallout.getGPSLat().isEmpty() &&
                lastCallout.getGPSLong() != null &&
                !lastCallout.getGPSLong().isEmpty()) {
            MarkerOptions currentCalloutMarkerOptions = new MarkerOptions();
            currentCalloutMarkerOptions.position(
                    new LatLng(Double.valueOf(lastCallout.getGPSLat()),
                            Double.valueOf(lastCallout.getGPSLong())));
            currentCalloutMarkerOptions.draggable(false);
            if (lastCallout.getAddress() != null) {
                currentCalloutMarkerOptions.title(lastCallout.getAddress());
            } else {
                currentCalloutMarkerOptions.title("Destination");
            }
            if (lastCallout.getCalloutType() != null) {
                currentCalloutMarkerOptions.snippet(lastCallout.getCalloutType());
            }
            currentCalloutMarkerOptions.icon(BitmapDescriptorFactory.defaultMarker(BitmapDescriptorFactory.HUE_RED));
            Marker currentCalloutMarker = inlineMap.addMarker(currentCalloutMarkerOptions);
            currentCalloutMarker.showInfoWindow();
            mapMarkers.add(currentCalloutMarker);
        }
    }

    private void setupKMLData() {
        if (kmlData == null) {
            String kmlUrl = getConfigItem(context, AppConstants.PROPERTY_KML_PAGE_URI, String.class);
            if (auth != null && auth.getHostURL() != null &&
                    kmlUrl != null && !kmlUrl.isEmpty()) {

                kmlData = new KMLData();
                //new KmlLoader(map).execute(Environment.getExternalStorageDirectory().getPath() + "FILE.kml");
                String URL = auth.getHostURL() + kmlUrl;
                new KmlLoader(kmlData).execute(URL);
            }
        }
    }

    double getLastGPSLatitude() {
        if (lastTrackedGEOLocation == null) {
            return 0;
        }
        return lastTrackedGEOLocation.getLatitude();
    }

    double getLastGPSLongitude() {
        if (lastTrackedGEOLocation == null) {
            return 0;
        }

        return lastTrackedGEOLocation.getLongitude();
    }

    private void showFragment(int id) {
        Fragment fragment = getSupportFragmentManager().findFragmentById(id);
        if (fragment != null) {
            fragment.getFragmentManager().beginTransaction()
                    //.setCustomAnimations(android.R.animator.fade_in, android.R.animator.fade_out)
                    .show(fragment)
                    .commitAllowingStateLoss();
        }
    }

    private void hideFragment(int id) {
        Fragment fragment = getSupportFragmentManager().findFragmentById(id);
        if (fragment != null) {
            fragment.getFragmentManager().beginTransaction()
                    //.setCustomAnimations(android.R.animator.fade_in, android.R.animator.fade_out)
                    .hide(fragment)
                    .commitAllowingStateLoss();
        }
    }

    private boolean isFragmentVisible(int id) {
        Fragment fragment = getSupportFragmentManager().findFragmentById(id);
        return (fragment != null && fragment.isVisible());
    }

    private static void scrollToBottom(final ScrollView scrollView, final TextView textView) {
        scrollView.post(new Runnable() {
            public void run() {
                scrollView.smoothScrollTo(0, textView.getBottom());
            }
        });
    }
}

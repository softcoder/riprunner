/*
 * Copyright 2014 Mark Vejvoda
 * Under GNU GPL v3.0
 */

package com.vejvoda.android.riprunner;

import android.annotation.TargetApi;
import android.app.Activity;
import android.app.AlertDialog;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.SharedPreferences.OnSharedPreferenceChangeListener;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager.NameNotFoundException;
import android.content.res.Configuration;
import android.media.Ringtone;
import android.media.RingtoneManager;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.preference.CheckBoxPreference;
import android.preference.EditTextPreference;
import android.preference.ListPreference;
import android.preference.Preference;
import android.preference.PreferenceActivity;
import android.preference.PreferenceFragment;
import android.preference.PreferenceManager;
import android.preference.RingtonePreference;
import android.text.TextUtils;
import android.util.Log;
import android.widget.Toast;

import java.io.IOException;
import java.net.HttpURLConnection;
import java.util.List;

import org.json.JSONException;
import org.json.JSONObject;

/**
 * A {@link PreferenceActivity} that presents a set of application settings. On
 * handset devices, settings are presented as a single list. On tablets,
 * settings are split by category, with category headers shown to the left of
 * the list of settings.
 * <p>
 * See <a href="http://developer.android.com/design/patterns/settings.html">
 * Android Design: Settings</a> for design guidelines and the <a
 * href="http://developer.android.com/guide/topics/ui/settings.html">Settings
 * API Guide</a> for more information on developing a Settings UI.
 */
public class SettingsActivity extends PreferenceActivity implements 
		OnSharedPreferenceChangeListener {
	/**
	 * Determines whether to always show the simplified settings UI, where
	 * settings are presented in a single list. When false, settings are shown
	 * as a master/detail two-pane view on tablets. When true, a single pane is
	 * shown on tablets.
	 */
	private static final boolean ALWAYS_SIMPLE_PREFS = false;

	@Override
	protected void onPostCreate(Bundle savedInstanceState) {
		super.onPostCreate(savedInstanceState);

		boolean auto_update_settings = this.getIntent().getBooleanExtra(
				"com.vejvoda.android.riprunner.auto_update_settings", false);
		
		setupSimplePreferencesScreen(auto_update_settings);
	}

	/**
	 * Shows the simplified settings UI if the device configuration if the
	 * device configuration dictates that a simplified, single-pane UI should be
	 * shown.
	 */
	private void setupSimplePreferencesScreen(boolean auto_update_settings) {
        try {
            if (!isSimplePreferences(this)) {
                return;
            }

            // In the simplified UI, fragments are not used at all and we instead
            // use the older PreferenceActivity APIs.
            setupGeneralPrefsUI();

            getGeneralSettingsDefaults();

            if(auto_update_settings) {
                EditTextPreference host_url = (EditTextPreference)findPreference(AppConstants.PROPERTY_WEBSITE_URL);
                if(host_url != null && !host_url.getText().isEmpty()) {
                    getMobileAppSettingsFromWebsite(host_url);
                }
            }
        }
        catch (Exception e) {
            // should never happen
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw e;
        }
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
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw new RuntimeException("Could not get package name: " + e);
        }
    }

    /**
     * @return Application's version code from the {@code PackageManager}.
     */
    private static String getAppVersionName(Context context) {
        try {
            PackageInfo packageInfo = context.getPackageManager()
                    .getPackageInfo(context.getPackageName(), 0);
            return packageInfo.versionName;
        } 
        catch (NameNotFoundException e) {
            // should never happen
        	Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw new RuntimeException("Could not get package name: " + e);
        }
    }

	void setupGeneralPrefsUI() {
        try {
            // Add 'general' preferences.
            addPreferencesFromResource(R.xml.pref_general);

            String appVersionName = getAppVersionName(this);
            Preference appVersion = (Preference) findPreference(AppConstants.PROPERTY_APP_VERSION);
            appVersion.setTitle(getResources().getString(R.string.pref_appversion) + " " + appVersionName);

            EditTextPreference regID = (EditTextPreference) findPreference(AppConstants.PROPERTY_REG_ID);

            final Context context = getBaseContext();
            if (context != null) {
                final SharedPreferences prefs = getGcmPreferences(context);
                if (prefs != null) {
                    String regid = prefs.getString(AppConstants.PROPERTY_REG_ID, "");
                    //regID.setTitle(regid);
                    regID.setText(regid);
                }
            }

            // Bind the summaries of EditText/List/Dialog/Ringtone preferences to
            // their values. When their values change, their summaries are updated
            // to reflect the new value, per the Android Design guidelines.
            bindPreferenceSummaryToValue(findPreference(AppConstants.PROPERTY_WEBSITE_URL));
            bindPreferenceSummaryToValue(findPreference(AppConstants.PROPERTY_SENDER_ID));
            bindPreferenceSummaryToValue(findPreference(AppConstants.PROPERTY_TRACKING_ENABLED));
        }
        catch (Exception e) {
            // should never happen
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw e;
        }
	}

	@Override
	protected void onResume() {
        try {
            super.onResume();
            getPreferenceScreen().getSharedPreferences()
                    .registerOnSharedPreferenceChangeListener(this);
        }
        catch (Exception e) {
            // should never happen
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw e;
        }
    }

	@Override
    protected void onPause() {
        try {
            super.onPause();
            getPreferenceScreen().getSharedPreferences()
                    .unregisterOnSharedPreferenceChangeListener(this);
        }
        catch (Exception e) {
            // should never happen
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw e;
        }
    }
    
	@Override
	public void onSharedPreferenceChanged(SharedPreferences sharedPreferences,
			String key) {
        try {
            Preference pref = findPreference(key);
            if (pref instanceof EditTextPreference) {
                EditTextPreference etp = (EditTextPreference) pref;
                pref.setTitle(etp.getTitle());
                pref.setSummary(etp.getText());
            }
        }
        catch (Exception e) {
            // should never happen
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw e;
        }
	}
	
	void getGeneralSettingsDefaults() {
		try {
            Preference button = (Preference)findPreference("button_get_defaults");
            button.setOnPreferenceClickListener(new Preference.OnPreferenceClickListener() {
                @Override
                public boolean onPreferenceClick(Preference arg0) {

                    // Get Default settings from the host
                    EditTextPreference host_url = (EditTextPreference)findPreference(AppConstants.PROPERTY_WEBSITE_URL);
                    if(host_url != null && !host_url.getText().isEmpty()) {
                        getMobileAppSettingsFromWebsite(host_url);
                    }

                    return true;
                }
            });
        }
        catch (Exception e) {
            // should never happen
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw e;
        }
	}

	private void getMobileAppSettingsFromWebsite(EditTextPreference host_url) {
		
		// Original main URL for app info
		final String URL_deprecated = host_url.getText() + "mobile_app_info.php";
		// Latest main URL for app info
		final String URL = host_url.getText() + "controllers/mobile-app-info-controller.php";
		final Context context = getBaseContext();
		
		new Thread(new Runnable() {
		    public void run() {            	
		    	
				try {
					Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner get defaults calling url [" + URL+ "]");
					// Ask the website for the mobile app settings
			        HttpURLConnection urlConnection = Utils.openHttpConnection(URL,"GET");
			        int code = urlConnection.getResponseCode();
			        if(code == HttpURLConnection.HTTP_OK) {
			        	final String responseString = Utils.getUrlConnectionResultSring(urlConnection).trim();
		                processMobileSettingsResponse(context, responseString);
		            }
		            else if(code == HttpURLConnection.HTTP_NOT_FOUND) {
		            	Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner fallback get defaults calling url [" + URL_deprecated + "]");
		            	
		            	urlConnection = Utils.openHttpConnection(URL_deprecated,"GET");
		            	code = urlConnection.getResponseCode();
				        if(code == HttpURLConnection.HTTP_OK) {
				        	final String responseString = Utils.getUrlConnectionResultSring(urlConnection).trim();
		            		processMobileSettingsResponse(context, responseString);
		            	}
		            }
		            
		            if(code != HttpURLConnection.HTTP_OK) {
		            	final String error = "code: " + code + " msg: " + Utils.getUrlConnectionErorResultSring(urlConnection);
		            	Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner get defaults got error [" + error + "]");
		            	
						runOnUiThread(new Runnable() {
					        public void run() {
								Toast.makeText(context, "*ERROR* receiving settings: " + error, Toast.LENGTH_LONG).show();
							}
						});
		            }
				} 
				catch (final IOException e) {
					Log.e(Utils.TAG, Utils.getLineNumber() + " RipRunner Error ", e);
					runOnUiThread(new Runnable() {
						public void run() {
							Toast.makeText(context, "#3 Error getting defaults:" +  e.getMessage(), Toast.LENGTH_LONG).show();
						}
					});
				} 
				catch (final JSONException e) {
					Log.e(Utils.TAG, Utils.getLineNumber() + " RipRunner Error ", e);
					runOnUiThread(new Runnable() {
						public void run() {
							Toast.makeText(context, "#4 Error getting defaults:" +  e.getMessage(), Toast.LENGTH_LONG).show();
						}
					});
				}
		    }

			void processMobileSettingsResponse(final Context context,
					final String responseString) throws IOException,
					JSONException {
				
				
				final int current_client_android_versionCode = getAppVersion(context);
				// Parse the JSON results
				Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner get defaults got response [" + responseString + "]");
				
				if(responseString != null && responseString.startsWith("{")) {
					final JSONObject json = new JSONObject( responseString );
					
					runOnUiThread(new Runnable() {
				        public void run() {
				        	EditTextPreference sender_id = (EditTextPreference)findPreference(AppConstants.PROPERTY_SENDER_ID);
				        	CheckBoxPreference tracking_enabled = (CheckBoxPreference)findPreference(AppConstants.PROPERTY_TRACKING_ENABLED);
				        	try {
								sender_id.setText(json.getString("gcm-projectid"));
								
								if(Integer.valueOf(json.getString("tracking-enabled")) != 0) {
									tracking_enabled.setChecked(true);
								}
								else {
									tracking_enabled.setChecked(false);
								}
								
								if(json.has(AppConstants.PROPERTY_LOGIN_PAGE_URI)) {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									editor.putString(AppConstants.PROPERTY_LOGIN_PAGE_URI, json.getString(AppConstants.PROPERTY_LOGIN_PAGE_URI));
									editor.commit();
								}
								else {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									//editor.putString(AppConstants.PROPERTY_LOGIN_PAGE_URI, "register_device.php");
									editor.putString(AppConstants.PROPERTY_LOGIN_PAGE_URI, "mobile-login/");
									editor.commit();
								}
								if(json.has(AppConstants.PROPERTY_CALLOUT_PAGE_URI)) {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									editor.putString(AppConstants.PROPERTY_CALLOUT_PAGE_URI, json.getString(AppConstants.PROPERTY_CALLOUT_PAGE_URI));
									editor.commit();
								}
								else {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									//editor.putString(AppConstants.PROPERTY_CALLOUT_PAGE_URI, "ci.php");
									editor.putString(AppConstants.PROPERTY_CALLOUT_PAGE_URI, "ci/");
									editor.commit();
								}
								
								if(json.has(AppConstants.PROPERTY_RESPOND_PAGE_URI)) {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									editor.putString(AppConstants.PROPERTY_RESPOND_PAGE_URI, json.getString(AppConstants.PROPERTY_RESPOND_PAGE_URI));
									editor.commit();
								}
								else {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									//editor.putString(AppConstants.PROPERTY_RESPOND_PAGE_URI, "cr.php");
									editor.putString(AppConstants.PROPERTY_RESPOND_PAGE_URI, "cr/");
									editor.commit();
								}
								
								if(json.has(AppConstants.PROPERTY_TRACKING_PAGE_URI)) {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									editor.putString(AppConstants.PROPERTY_TRACKING_PAGE_URI, json.getString(AppConstants.PROPERTY_TRACKING_PAGE_URI));
									editor.commit();
								}
								else {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									//editor.putString(AppConstants.PROPERTY_TRACKING_PAGE_URI, "ct.php");
									editor.putString(AppConstants.PROPERTY_TRACKING_PAGE_URI, "ct/");
									editor.commit();
								}

								if(json.has(AppConstants.PROPERTY_KML_PAGE_URI)) {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									editor.putString(AppConstants.PROPERTY_KML_PAGE_URI, json.getString(AppConstants.PROPERTY_KML_PAGE_URI));
									editor.commit();
								}
								else {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									editor.putString(AppConstants.PROPERTY_KML_PAGE_URI, "");
									editor.commit();
								}

								if(json.has(AppConstants.PROPERTY_ANDROID_ERROR_PAGE_URI)) {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									editor.putString(AppConstants.PROPERTY_ANDROID_ERROR_PAGE_URI, json.getString(AppConstants.PROPERTY_ANDROID_ERROR_PAGE_URI));
									editor.commit();
								}
								else {
									SharedPreferences sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context);
									SharedPreferences.Editor editor = sharedPrefs.edit();
									editor.putString(AppConstants.PROPERTY_ANDROID_ERROR_PAGE_URI, "");
									editor.commit();
								}
								
								Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Successfully received app settings.");
								Toast.makeText(context, "Successfully received app settings.", Toast.LENGTH_LONG).show();
								
								if(json.has("android:versionCode")) {
									int current_server_android_versionCode = json.getInt("android:versionCode");
									Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner client ver [" + current_server_android_versionCode + "] server [" + current_server_android_versionCode + "]");
									
									if(current_server_android_versionCode > current_client_android_versionCode) {
										createAndShowUpgradeDialog(getResources().getString(R.string.upgrade_message));
									}
								}
							} 
				        	catch (JSONException e) {
				        		Log.e(Utils.TAG, Utils.getLineNumber() + " RipRunner Error ", e);
				            	Toast.makeText(context, "#1 Error getting defaults:" +  e.getMessage(), Toast.LENGTH_LONG).show();
							}
				        	catch (Exception e) {
				        		Log.e(Utils.TAG, Utils.getLineNumber() + " RipRunner Error ", e);
				            	Toast.makeText(context, "#2 Error getting defaults:" +  e.getMessage(), Toast.LENGTH_LONG).show();
				        	}
				        }
				    });
				}
			}
		  }).start();
	}
	
	 private void createAndShowUpgradeDialog(final String title) {
         try {
             final Activity ac = this;
             runOnUiThread(new Runnable() {
                 public void run() {

                 final Context context = getBaseContext();
                 AlertDialog.Builder builder = new AlertDialog.Builder(ac);
                 builder.setTitle(title);

                 builder.setPositiveButton(android.R.string.yes, new DialogInterface.OnClickListener() {
                     public void onClick(DialogInterface dialog, int id) {
                         dialog.dismiss();

                         EditTextPreference host_url = (EditTextPreference)findPreference(AppConstants.PROPERTY_WEBSITE_URL);

                         String updateAPK_URL = host_url.getText() + "apk/" + Utils.APK_NAME;
                         Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner upgrade URL [" + updateAPK_URL + "]");

                         try {
                            Intent intent = new Intent(Intent.ACTION_VIEW );
                            intent.setData(Uri.parse(updateAPK_URL));
                            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                            context.startActivity(intent);
                         }
                         catch(Exception e) {
                            Log.e(Utils.TAG, Utils.getLineNumber() + " RipRunner upgrade Error ", e);
                            Toast.makeText(context, "#1 Error upgrading:" +  e.getMessage(), Toast.LENGTH_LONG).show();
                         }
                    }
                 });
                 builder.setNegativeButton(android.R.string.cancel, new DialogInterface.OnClickListener() {
                     public void onClick(DialogInterface dialog, int id) {
                         dialog.dismiss();
                     }
                 });
                 AlertDialog dialog = builder.create();
                 dialog.show();
                 }
            });
         }
         catch (Exception e) {
             // should never happen
             Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
             throw e;
         }
	}
	 
	/** {@inheritDoc} */
	@Override
	public boolean onIsMultiPane() {
		return isXLargeTablet(this) && !isSimplePreferences(this);
	}

	/**
	 * Helper method to determine if the device has an extra-large screen. For
	 * example, 10" tablets are extra-large.
	 */
	private static boolean isXLargeTablet(Context context) {
		return (context.getResources().getConfiguration().
				screenLayout & Configuration.SCREENLAYOUT_SIZE_MASK) >= 
								Configuration.SCREENLAYOUT_SIZE_XLARGE;
	}

	/**
	 * Determines whether the simplified settings UI should be shown. This is
	 * true if this is forced via {@link #ALWAYS_SIMPLE_PREFS}, or the device
	 * doesn't have newer APIs like {@link PreferenceFragment}, or the device
	 * doesn't have an extra-large screen. In these cases, a single-pane
	 * "simplified" settings UI should be shown.
	 */
	private static boolean isSimplePreferences(Context context) {
		return ALWAYS_SIMPLE_PREFS
				|| Build.VERSION.SDK_INT < Build.VERSION_CODES.HONEYCOMB
				|| !isXLargeTablet(context);
	}

	/** {@inheritDoc} */
	@Override
	@TargetApi(Build.VERSION_CODES.HONEYCOMB)
	public void onBuildHeaders(List<Header> target) {
        try {
            if (!isSimplePreferences(this)) {
                loadHeadersFromResource(R.xml.pref_headers, target);
            }
        }
        catch (Exception e) {
            // should never happen
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw e;
        }
	}

	/**
	 * A preference value change listener that updates the preference's summary
	 * to reflect its new value.
	 */
	private static Preference.OnPreferenceChangeListener sBindPreferenceSummaryToValueListener = 
			new Preference.OnPreferenceChangeListener() {
		@Override
		public boolean onPreferenceChange(Preference preference, Object value) {

            try {
                String stringValue = value.toString();

                if (preference instanceof ListPreference) {
                    // For list preferences, look up the correct display value in
                    // the preference's 'entries' list.
                    ListPreference listPreference = (ListPreference) preference;
                    int index = listPreference.findIndexOfValue(stringValue);

                    // Set the summary to reflect the new value.
                    preference
                            .setSummary(index >= 0 ? listPreference.getEntries()[index]
                                    : null);

                }
                else if (preference instanceof RingtonePreference) {
                    // For ringtone preferences, look up the correct display value
                    // using RingtoneManager.
                    //if (TextUtils.isEmpty(stringValue)) {
                        // Empty values correspond to 'silent' (no ringtone).
                        //preference.setSummary(R.string.pref_ringtone_silent);

                    //}
                    //else {
					if (!TextUtils.isEmpty(stringValue)) {
                        Ringtone ringtone = RingtoneManager.getRingtone(
                                preference.getContext(), Uri.parse(stringValue));

                        if (ringtone == null) {
                            // Clear the summary if there was a lookup error.
                            preference.setSummary(null);
                        }
                        else {
                            // Set the summary to reflect the new ringtone display
                            // name.
                            String name = ringtone
                                    .getTitle(preference.getContext());
                            preference.setSummary(name);
                        }
                    }

                }
                else {
                    // For all other preferences, set the summary to the value's
                    // simple string representation.
                    preference.setSummary(stringValue);
                }
                return true;
            }
            catch (Exception e) {
                // should never happen
                Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
                throw e;
            }
		}
	};

	/**
	 * Binds a preference's summary to its value. More specifically, when the
	 * preference's value is changed, its summary (line of text below the
	 * preference title) is updated to reflect the value. The summary is also
	 * immediately updated upon calling this method. The exact display format is
	 * dependent on the type of preference.
	 * 
	 * @see #sBindPreferenceSummaryToValueListener
	 */
	private static void bindPreferenceSummaryToValue(Preference preference) {
        try {
            // Set the listener to watch for value changes.
            preference.setOnPreferenceChangeListener(
                    sBindPreferenceSummaryToValueListener);

            SharedPreferences sharedPref = PreferenceManager.getDefaultSharedPreferences(
                    preference.getContext());

            // Trigger the listener immediately with the preference's
            // current value.
            if (preference instanceof CheckBoxPreference) {
                sBindPreferenceSummaryToValueListener.onPreferenceChange(
                        preference,
                        sharedPref.getBoolean(preference.getKey(), true));
            }
            else {
                sBindPreferenceSummaryToValueListener.onPreferenceChange(
                    preference,
                        sharedPref.getString(preference.getKey(), ""));
            }
        }
        catch (Exception e) {
            // should never happen
            Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
            throw e;
        }
	}

	/**
	 * This fragment shows general preferences only. It is used when the
	 * activity is showing a two-pane settings UI.
	 */
	@TargetApi(Build.VERSION_CODES.HONEYCOMB)
	public static class GeneralPreferenceFragment extends PreferenceFragment {
		@Override
		public void onCreate(Bundle savedInstanceState) {
            try {
                super.onCreate(savedInstanceState);
                addPreferencesFromResource(R.xml.pref_general);

                // Bind the summaries of EditText/List/Dialog/Ringtone preferences
                // to their values. When their values change, their summaries are
                // updated to reflect the new value, per the Android Design
                // guidelines.
                bindPreferenceSummaryToValue(findPreference(AppConstants.PROPERTY_WEBSITE_URL));
                bindPreferenceSummaryToValue(findPreference(AppConstants.PROPERTY_SENDER_ID));
                bindPreferenceSummaryToValue(findPreference(AppConstants.PROPERTY_TRACKING_ENABLED));
            }
            catch (Exception e) {
                // should never happen
                Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
                throw e;
            }
		}
	}

	/**
	 * This fragment shows notification preferences only. It is used when the
	 * activity is showing a two-pane settings UI.
	 */
	@TargetApi(Build.VERSION_CODES.HONEYCOMB)
	public static class NotificationPreferenceFragment extends
			PreferenceFragment {
		@Override
		public void onCreate(Bundle savedInstanceState) {
            try {
                super.onCreate(savedInstanceState);
                //addPreferencesFromResource(R.xml.pref_notification);

                // Bind the summaries of EditText/List/Dialog/Ringtone preferences
                // to their values. When their values change, their summaries are
                // updated to reflect the new value, per the Android Design
                // guidelines.
                //bindPreferenceSummaryToValue(findPreference("notifications_new_message_ringtone"));
            }
            catch (Exception e) {
                // should never happen
                Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
                throw e;
            }
		}
	}

	/**
	 * This fragment shows data and sync preferences only. It is used when the
	 * activity is showing a two-pane settings UI.
	 */
	@TargetApi(Build.VERSION_CODES.HONEYCOMB)
	public static class DataSyncPreferenceFragment extends PreferenceFragment {
		@Override
		public void onCreate(Bundle savedInstanceState) {
            try {
                super.onCreate(savedInstanceState);
                //addPreferencesFromResource(R.xml.pref_data_sync);

                // Bind the summaries of EditText/List/Dialog/Ringtone preferences
                // to their values. When their values change, their summaries are
                // updated to reflect the new value, per the Android Design
                // guidelines.
                //bindPreferenceSummaryToValue(findPreference("sync_frequency"));
            }
            catch (Exception e) {
                // should never happen
                Log.e(Utils.TAG, Utils.getLineNumber() + ": Rip Runner Error", e);
                throw e;
            }
		}
	}
	
    private SharedPreferences getGcmPreferences(Context context) {
        return getSharedPreferences(AppMainActivity.class.getSimpleName(),
                Context.MODE_PRIVATE);
    }
	
}

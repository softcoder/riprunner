# riprunner

Rip Runner Mobile Client

## Getting Started

There are a few things you will need to do in order to compile the android client for your installation:

1. Edit the file: https://github.com/softcoder/riprunner/blob/master/flutter/riprunner/android/app/src/main/AndroidManifest.xml

        <meta-data android:name="com.google.android.geo.API_KEY"
                   android:value="Enter your map api key here"/>
                   
Acquire a google maps API key: https://developers.google.com/maps/documentation/javascript/get-api-key
Then edit the value 'Enter your map api key here' and repalce with your API key.

2. If you plan to support Firebase Cloud Messaging (FCM) push notifications you will need to register an accoutn with Google Firebase Cloud Messaging here: https://firebase.google.com/docs/android/setup
Follow instructions to download google-services.json and place that file in the folder: android/app

3. Compile the application and produce the APK:
flutter build apk

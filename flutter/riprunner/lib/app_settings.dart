import 'package:flutter/material.dart';
import 'dart:convert';

import 'package:file_picker/file_picker.dart';
import 'package:rflutter_alert/rflutter_alert.dart';

import 'app_constants.dart';
import 'common/utils.dart';

class AppSettingsPage extends StatefulWidget {
  static String tag = 'settings-page';

  @override
  _AppSettingsState createState() => new _AppSettingsState();
}

class _AppSettingsState extends State<AppSettingsPage> {

  String websiteUrlStr;
  TextEditingController textCtlWebsiteUrl = new TextEditingController();
  String gcmProjectStr;
  TextEditingController textCtlGCMProject = new TextEditingController();
  bool trackingGeo = false;
  String deviceidStr;
  String audioStreamRawUrl;
  String customPagerAudioFile;

  void dispose() {
    textCtlWebsiteUrl.dispose();
    textCtlGCMProject.dispose();
    super.dispose();
  }

  Future<void> loadState() async {
    websiteUrlStr = await Utils.getConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL);
    gcmProjectStr = await Utils.getConfigItem<String>(AppConstants.PROPERTY_SENDER_ID);
    trackingGeo = await Utils.getConfigItem<bool>(AppConstants.PROPERTY_TRACKING_ENABLED);
    deviceidStr = await Utils.getConfigItem<String>(AppConstants.PROPERTY_REG_ID);
    audioStreamRawUrl = await Utils.getConfigItem<String>(AppConstants.PROPERTY_AUDIO_STREAM_RAW_URL);
    customPagerAudioFile = await Utils.getConfigItem<String>(AppConstants.PROPERTY_CUSTOM_PAGER_AUDIO_FILE);
    setState(() {
      websiteUrlStr = websiteUrlStr;
      gcmProjectStr = gcmProjectStr;
      trackingGeo = trackingGeo;
      deviceidStr = deviceidStr;
      audioStreamRawUrl = audioStreamRawUrl;
      customPagerAudioFile = customPagerAudioFile;
    });
  }

  @override
  void initState() {
    super.initState();
    loadState();
  }  

  void processLoadResult(String result) {
    Map<String, dynamic> config = json.decode(result);
    setState(() {
      websiteUrlStr = textCtlWebsiteUrl.text;
      gcmProjectStr = config['gcm-projectid'];
      trackingGeo = config['tracking-enabled'] == '1' || config['tracking-enabled'] == 'true' ? true : false;

      // "android:versionCode":"9",
      // "android:versionName":"1.8",
      // "login_page_uri":"mobile-login\/",
      // "callout_page_uri":"ci\/",
      // "respond_page_uri":"cr\/",
      // "tracking_page_uri":"ct\/",
      // "kml_page_uri":"kml\/boundaries.kml",
      // "android_error_page_uri":"android-error.php"

      audioStreamRawUrl = config['audio_stream_raw'];

      textCtlGCMProject.text = gcmProjectStr;
    });
  }

  @override
  Widget build(BuildContext context) {

    final BuildContext builCtx = context;

    final logo = Hero(
      tag: 'hero',
      child: CircleAvatar(
        backgroundColor: Colors.transparent,
        radius: 68.0,
        child: Image.asset('assets/generic_logo3.png'),
        ),
    );

   final websiteLabel = FlatButton(
      child: Text(
        'Website Url:',
        style: TextStyle(color: Colors.black54)),
        onPressed: () {  },
    );

    textCtlWebsiteUrl.text = websiteUrlStr ?? '';
    final websiteUrl = TextField(
      controller: textCtlWebsiteUrl,
      autofocus: false,
      onChanged: (text) {
        websiteUrlStr = text;
      },
      decoration: InputDecoration(
        hintText: 'Website Url',
        contentPadding: EdgeInsets.fromLTRB(20.0, 10.0, 20.0, 10.0),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32.0)
        ),
      ),
    );

   final gcmLabel = FlatButton(
      child: Text(
        'Google Cloud Project Number:',
        style: TextStyle(color: Colors.black54)),
        onPressed: () {  },
    );

    textCtlGCMProject.text = gcmProjectStr ?? '';
    final gcmProject = TextField(
      controller: textCtlGCMProject,
      onChanged: (text) {
        gcmProjectStr = text;
      },
      autofocus: false,
      decoration: InputDecoration(
        hintText: 'GCM Project Number',
        contentPadding: EdgeInsets.fromLTRB(20.0, 10.0, 20.0, 10.0),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32.0)
        ),
      ),
    );

   final geoTrackingLabel = FlatButton(
      child: Text(
        'Enable GEO Tracking:',
        style: TextStyle(color: Colors.black54)),
        onPressed: () {  },
    );

    final enabledGeoTracking = Checkbox(
      value: trackingGeo ?? false,
      onChanged: (bool value) {
        setState(() {
          trackingGeo = value;
        });
      },
    );

   final deviceIdLabelTitle = FlatButton(
      child: Text(
        'Device Id:',
        style: TextStyle(color: Colors.black54)),
        onPressed: () {  },
    );

   final deviceIdLabel = FlatButton(
      child: Text(
        deviceidStr ?? 'n/a',
        style: TextStyle(color: Colors.black54)),
        onPressed: () {   },
    );

    final customPagerSoundButton = Padding(
      padding: EdgeInsets.symmetric(vertical: 1.0),
      child: Material(
        borderRadius: BorderRadius.circular(30.0),
        shadowColor: Colors.lightBlueAccent.shade100,
        elevation: 5.0,
        child: MaterialButton(
          minWidth: 200.0,
          height: 42.0,
          onPressed: () {
            FilePicker.getFilePath(type: FileType.AUDIO).then((file) {
              customPagerAudioFile = file;
              Utils.setConfigItem<String>(AppConstants.PROPERTY_CUSTOM_PAGER_AUDIO_FILE, customPagerAudioFile);
            });
          },
          color: Colors.lightBlueAccent,
          child: Text('Custom Pager Sound',style: TextStyle(color: Colors.white)),
        ),
      ),
    );

    final loadButton = Padding(
      padding: EdgeInsets.symmetric(vertical: 1.0),
      child: Material(
        borderRadius: BorderRadius.circular(30.0),
        shadowColor: Colors.lightBlueAccent.shade100,
        elevation: 5.0,
        child: MaterialButton(
          minWidth: 200.0,
          height: 42.0,
          onPressed: () {
            loadWebsiteSettings(builCtx);
          },
          color: Colors.lightBlueAccent,
          child: Text('Load settings from Website',style: TextStyle(color: Colors.white)),
        ),
      ),
    );

    final closeButton = Padding(
      padding: EdgeInsets.symmetric(vertical: 1.0),
      child: Material(
        borderRadius: BorderRadius.circular(30.0),
        shadowColor: Colors.lightBlueAccent.shade100,
        elevation: 5.0,
        child: MaterialButton(
          minWidth: 200.0,
          height: 42.0,
          onPressed: () {
            Navigator.pop(context);
          },
          color: Colors.lightBlueAccent,
          child: Text('Return',style: TextStyle(color: Colors.white)),
        ),
      ),
    );
    
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: ListView(
          shrinkWrap: true,
          padding: EdgeInsets.only(left: 24.0, right: 24.0),
          children: <Widget>[
            logo,
            //SizedBox(height: 2.0),
            websiteLabel,
            websiteUrl,
            //SizedBox(height: 2.0),
            gcmLabel,
            gcmProject,
            //SizedBox(height: 2.0),
            Row(children: <Widget>[
              geoTrackingLabel,
              enabledGeoTracking,
            ],),
            //SizedBox(height: 2.0),
            Row(children: <Widget>[
              deviceIdLabelTitle,
              deviceIdLabel,
            ],),
            //SizedBox(height: 2.0),
            customPagerSoundButton,
            loadButton,
            closeButton,
           ],
        ),
      ),
    );
  }

  void loadWebsiteSettings(BuildContext builCtx) {
    String url = textCtlWebsiteUrl.text + (!textCtlWebsiteUrl.text.endsWith('/') ? '/' : '') + 'controllers/mobile-app-info-controller.php';
    Utils.apiRequest(url, null, APIRequestType.GET,false).then((data) {
      processLoadResult(data);
      Utils.setConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL, textCtlWebsiteUrl.text);
      Utils.setConfigItem<String>(AppConstants.PROPERTY_SENDER_ID, textCtlGCMProject.text);
      Utils.setConfigItem<bool>(AppConstants.PROPERTY_TRACKING_ENABLED, trackingGeo);
      Utils.setConfigItem<String>(AppConstants.PROPERTY_REG_ID, deviceidStr);
      Utils.setConfigItem<String>(AppConstants.PROPERTY_AUDIO_STREAM_RAW_URL, audioStreamRawUrl);
    }).catchError((onError) {
    
      print("In loadWebsiteSettings $onError");
      Alert(
        context: builCtx,
        type: AlertType.error,
        title: "Error",
        desc: onError.toString(),
        buttons: [
          DialogButton(
            child: Text(
              "Ok",
              style: TextStyle(color: Colors.white, fontSize: 20),
            ),
            onPressed: () {
              Navigator.of(builCtx, rootNavigator: true).pop();
            },
            color: Color.fromRGBO(0, 179, 134, 1.0),
          ),
        ],
      ).show();
    });
  }
}
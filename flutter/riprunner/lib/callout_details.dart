import 'dart:io' as io;
import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';

import 'package:modal_progress_hud/modal_progress_hud.dart';
import 'package:audioplayer/audioplayer.dart';
import 'package:flutter_radio/flutter_radio.dart';
import 'package:geolocator/geolocator.dart';

import 'app_constants.dart';
import 'common/sounds.dart';
import 'common/utils.dart';

class CalloutDetailsPage extends StatefulWidget {
  static String tag = 'callout-details';

  @override
  _CalloutDetailsPageState createState() => new _CalloutDetailsPageState();
}

const double ResponderDefaultFontSize = 18.0;
const double ResponderDefaultFontDroppedSize = 18.0;
//double ResponderTimeFontSize = 10.0;
//double ResponderDefaultWidth = 95;
const double ResponderDefaultHeight = 30;

const double ResponderNameWidth = 165;
const double ResponderStatusWidth = 180;
//double ResponderTimeWidth = 100;
const double ResponderETAWidth = 60;

const int UNSELECTED_STATUS_ID = -1;

class _CalloutDetailsPageState extends State<CalloutDetailsPage> with AutomaticKeepAliveClientMixin<CalloutDetailsPage> {

  @override
  bool get wantKeepAlive => true;

  String websiteUrlStr;
  String firehallId;
  String userId='';
  Map<String, dynamic> liveCallout = {};
  String audioStreamRawUrl;
  bool _inProgress = false;
  Timer pollCallouts;
  Timer trackCallouts;

  AudioPlayer audioPlayer = new AudioPlayer();
  bool isRadioStreaming = false;

  Position geoPosition;
  StreamSubscription<Position> positionStream;

  void dispose() {
    if(pollCallouts != null) {
      pollCallouts.cancel();
    }
    if(trackCallouts != null) {
      trackCallouts.cancel();
    }
    if(positionStream != null) {
      positionStream.cancel();
    }
    super.dispose();
  }

  Future<void> trackGeo() async {
    try {
      bool trackingGeo = await Utils.getConfigItem<bool>(AppConstants.PROPERTY_TRACKING_ENABLED);
      if(trackingGeo && isCalloutActive()) {
        websiteUrlStr = await Utils.getConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL);
        firehallId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_FIREHALL_ID);
        userId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_USER_ID);
                
        String lat = (geoPosition != null && geoPosition.latitude != null ? geoPosition.latitude.toString() : '');
        String long = (geoPosition != null && geoPosition.longitude != null ? geoPosition.longitude.toString() : '');

        String url = websiteUrlStr + (!websiteUrlStr.endsWith('/') ? '/' : '') + 
          'ct/cid='+liveCallout['id']+
          '&fhid='+firehallId+'&ckid='+liveCallout['callkey']+'&uid='+userId+
          '&lat='+lat+
          '&long='+long;
        Utils.apiRequest(url, null, APIRequestType.GET,false).
        then((data) {
          
        }).
        catchError((e) {
          print(e.toString());
        }).whenComplete(() {
        });
      }
    }
    finally {
    }
  }

  Map<String, dynamic> processLoadResult(String result) {
    Map<String, dynamic> resultJSON;
    try {
      if(result != null && result != '') {
        resultJSON = json.decode(result);
      }
      return resultJSON;
    }
    catch(e) {
      print("In processLoadResult error for text [$result] message: $e.toString()");
      throw e;
    }
  }

  Future<void> updateStatus(var callout, var responder, var status) async {
    bool updateInProgress = startInProgress();
    try {
      websiteUrlStr = await Utils.getConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL);
      firehallId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_FIREHALL_ID);
      userId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_USER_ID);
      
      String lat = (geoPosition != null && geoPosition.latitude != null ? geoPosition.latitude.toString() : '');
      String long = (geoPosition != null && geoPosition.longitude != null ? geoPosition.longitude.toString() : '');

      String url = websiteUrlStr + (!websiteUrlStr.endsWith('/') ? '/' : '') + 
        'controllers/callout-response-controller.php?cid='+callout['id']+
        '&fhid='+firehallId+'&ckid='+callout['callkey']+'&uid='+responder['user_id']+
        '&lat='+lat+
        '&long='+long+
        '&member_id='+responder['user_id']+'&status='+status;
      Utils.apiRequest(url, null, APIRequestType.GET,false).
      then((data) {
        loadCalloutData().then((X) {
          endInProgress(updateInProgress);
        }).
        catchError((e) {
          print(e.toString());
          endInProgress(updateInProgress);
        }).whenComplete(() {
          endInProgress(updateInProgress);
        });
      }).
      catchError((e) {
        print(e.toString());
        endInProgress(updateInProgress);
      }).whenComplete(() {
        endInProgress(updateInProgress);
      });
    }
    finally {
      endInProgress(updateInProgress);
    }
  }

  Future loadCalloutData() async {
    bool updateInProgress = startInProgress();

    websiteUrlStr = await Utils.getConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL);
    firehallId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_FIREHALL_ID);
    userId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_USER_ID);
    audioStreamRawUrl = await Utils.getConfigItem<String>(AppConstants.PROPERTY_AUDIO_STREAM_RAW_URL);
    String customPagerAudioFile = await Utils.getConfigItem<String>(AppConstants.PROPERTY_CUSTOM_PAGER_AUDIO_FILE);
    
    String previousCalloutId = (liveCallout != null ? liveCallout['id'] : '');
    String url = websiteUrlStr + (!websiteUrlStr.endsWith('/') ? '/' : '') + 
      'angular-services/live-callout-service.php/details?fhid='+firehallId;
    Utils.apiRequest(url, null, APIRequestType.GET,false).
      then((responseString) {
        Map<String, dynamic> responseMap = processLoadResult(responseString);
        setState(() {
          websiteUrlStr = websiteUrlStr;
          firehallId = firehallId;
          userId = userId;
          liveCallout = responseMap;

          if(isCalloutActive() && previousCalloutId != liveCallout['id']) {
            if(customPagerAudioFile != null && customPagerAudioFile != '' && io.File(customPagerAudioFile).existsSync()) {
              SoundUtils.playSound(audioPlayer,customPagerAudioFile, ResourceType.LocalFile);
            }
            else {
              String url = websiteUrlStr + (!websiteUrlStr.endsWith('/') ? '/' : '') + 
                'sounds/pager_tone_pg.mp3';
              SoundUtils.playSound(audioPlayer,url, ResourceType.URL);
            }
          }
        });
        endInProgress(updateInProgress);
      }).
      catchError((e) {
        print(e.toString());
        endInProgress(updateInProgress);
      }).whenComplete(() {
        endInProgress(updateInProgress);
      });
  }

  bool startInProgress() {
    bool updateInProgress = !_inProgress;
    if(updateInProgress) {
      setState(() {
        _inProgress = true;
      });
    }
    return updateInProgress;
  }

  void endInProgress(bool updateInProgress) {
    if(updateInProgress) {
      setState(() {
        _inProgress = false;
      });
    }
  }

  @override
  void initState() {
    super.initState();

    FlutterRadio.audioStart();
    Geolocator().getCurrentPosition(desiredAccuracy: LocationAccuracy.high).then((position) {
      geoPosition = position;
      var geolocator = Geolocator();
      var locationOptions = LocationOptions(accuracy: LocationAccuracy.high, distanceFilter: 10);
      positionStream = geolocator.getPositionStream(locationOptions).
      listen((Position position) {
        geoPosition = position;
      });      
    });
    loadCalloutData();
    pollCallouts = new Timer.periodic(Duration(seconds: 20), (Timer timer) => this.loadCalloutData());
    trackCallouts = new Timer.periodic(Duration(seconds: 30), (Timer timer) => this.trackGeo());
  }  

  Future<void> startRadioStream() async {
    if(await FlutterRadio.isPlaying()) {
      FlutterRadio.stop();
    }
    FlutterRadio.play(url: audioStreamRawUrl);
    setState(() {
      isRadioStreaming = true;
    });
  }

  void stopRadioStream() {
    FlutterRadio.isPlaying().then((playing) {
      if(playing) {
        FlutterRadio.pause(url: audioStreamRawUrl);
      }
    });
    setState(() {
      isRadioStreaming = false;
    });
  }

  void buildCalloutHeader(List<Widget> calloutWidgetParts) {

    calloutWidgetParts.add(Padding(
      padding: EdgeInsets.all(2.0),
      child: Text(
        liveCallout['address'],
        style: TextStyle(fontSize: 25.0, color: Colors.cyan),
      ),
    ));
    calloutWidgetParts.add(Padding(
      padding: EdgeInsets.all(2.0),
      child: Text(
        liveCallout['type'] + ' - ' + liveCallout['type_desc'],
        style: TextStyle(fontSize: 20.0, color: Colors.yellow),
      ),
    ));
    calloutWidgetParts.add(Padding(
      padding: EdgeInsets.all(2.0),
      child: Text(
        liveCallout['time'],
        style: TextStyle(fontSize: 16.0, color: Colors.white),
      ),
    ));
    calloutWidgetParts.add(Padding(
      padding: EdgeInsets.all(2.0),
      child: Text(
        liveCallout['status_desc'],
        style: TextStyle(fontSize: 20.0, color: Colors.redAccent),
      ),
    ));
    // final calloutComments = Padding(
    //   padding: EdgeInsets.all(8.0),
    //   child: Text(
    //     callout['comment'],
    //     style: TextStyle(fontSize: 16.0, color: Colors.white),
    //   ),
    // );
  }

  List<Widget> buildCalloutResponderHeader() {
    List<Widget> responders = [
        Row(
          children: <Widget> [
            new Container(
              width: ResponderNameWidth,
              height: ResponderDefaultHeight,
              padding: const EdgeInsets.all(3.0),
              decoration: new BoxDecoration(
                border: new Border.all(color: Colors.blueAccent),
                color: Colors.blueGrey
              ),
              child: Text('Responder',style: TextStyle(fontSize: ResponderDefaultFontSize, color: Colors.white))
              ),
          new Container(
              width: ResponderStatusWidth,
              height: ResponderDefaultHeight,
              padding: const EdgeInsets.all(3.0),
              decoration: new BoxDecoration(
                border: new Border.all(color: Colors.blueAccent),
                color: Colors.blueGrey
              ),
              child: Text('Status',style: TextStyle(fontSize: ResponderDefaultFontSize, color: Colors.white))),
          // new Container(
          //     width: ResponderTimeWidth,
          //     height: ResponderDefaultHeight,
          //     padding: const EdgeInsets.all(3.0),
          //     decoration: new BoxDecoration(
          //       border: new Border.all(color: Colors.blueAccent),
          //       color: Colors.blueGrey
          //     ),
          //     child: Text('Response Time',style: TextStyle(fontSize: ResponderDefaultFontSize, color: Colors.white))),
          new Container(
              width: ResponderETAWidth,
              height: ResponderDefaultHeight,
              padding: const EdgeInsets.all(3.0),
              decoration: new BoxDecoration(
                border: new Border.all(color: Colors.blueAccent),
                color: Colors.blueGrey
              ),
              child: Text('ETA',style: TextStyle(fontSize: ResponderDefaultFontSize, color: Colors.white)))
        ],
      ),
    ];
    return responders;
  }

  bool hasCurrentUserResponded(bool checkUnselectedStatus) {
    if(liveCallout != null && liveCallout.isNotEmpty) {
      List responses = liveCallout['callout_details_responding_list'];
      // Check if the current user responded yet
      for (var responder in responses ?? []) {
        if((responder['user_id'] ?? '') == userId) {
          if(checkUnselectedStatus == false || 
             ((responder['status'] ?? '') != UNSELECTED_STATUS_ID.toString())) {
            return true;
          }
          break;
        }
      }
    }
    return false;
  }

  List buildResponderList() {
    List responses = liveCallout['callout_details_responding_list'];
    bool currentUserIsResponding = hasCurrentUserResponded(false);

    // Add the current user to the list so they can respond
    // if the current user has not responded yet
    if(currentUserIsResponding == false) {
      addResponderWithUnselectedStatus(responses);
    }
    return responses;
  }

  void addResponderWithUnselectedStatus(List responses) {
    Map responder = {};
    responder['user_id'] = userId;
    responder['status']  = UNSELECTED_STATUS_ID.toString();
    responder['eta']     = '?';
        
    responses.insert(0, responder);
  }

  List<DropdownMenuItem<String>> buildStatusList() {
    var statuses = [];

    Map<String,String> noneSelected = { };
    if(hasCurrentUserResponded(true) == false) {
      noneSelected['id'] = UNSELECTED_STATUS_ID.toString();
      noneSelected['displayName'] = '';
      statuses.add(noneSelected);
    }
    statuses.addAll(liveCallout['callout_status_defs']);
    
    List<DropdownMenuItem<String>> statusList = [];
    for (var status in statuses) {
      statusList.add(new DropdownMenuItem(
          value: status['id'],
          child: Text(status['displayName'], 
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(fontSize: ResponderDefaultFontDroppedSize, color: Colors.red))
      ));
    }
    return statusList;
  }

  bool isResponderCurrentUser(var responder) {
    return (responder['user_id'] == userId);
  }

  Widget getStatusWidget(var responder, List<DropdownMenuItem<String>> statusList) {
    if(isResponderCurrentUser(responder)) {
      return DropdownButton(
            isDense: true,
            value: responder['status'] ?? '?',
            items: statusList,
            onChanged: (status) {
              if(status != responder['status']) {
                updateStatus(liveCallout, responder, status).
                  catchError((e) {
                    print(e.toString());
                  });
              }
            }
        );
    }
    else {
      return Text(responder['responder_display_status'] ?? '?',
                  style: TextStyle(fontSize: ResponderDefaultFontSize, color: Colors.white));
    }
  }

  void buildResponderRowList(List responses, List<Widget> responders, List<DropdownMenuItem<String>> statusList) {
    for (var responder in responses ?? []) {
      responders.add(Row(
          children: <Widget> [
            new Container(
              width: ResponderNameWidth,
              height: ResponderDefaultHeight,
              padding: const EdgeInsets.all(3.0),
              decoration: new BoxDecoration(
                border: new Border.all(color: Colors.blueAccent)
              ),
              child: Text(responder['user_id'] ?? '?',
                        style: TextStyle(fontSize: ResponderDefaultFontSize, color: Colors.white))),
            new Container(
              width: ResponderStatusWidth,
              height: ResponderDefaultHeight,
              padding: const EdgeInsets.all(1.5),
              decoration: new BoxDecoration(
                border: new Border.all(color: Colors.blueAccent),
                color: isResponderCurrentUser(responder) ? Colors.white : Colors.black),
              child: getStatusWidget(responder, statusList),
            ),
            // new Container(
            //   width: ResponderTimeWidth,
            //   height: ResponderDefaultHeight,
            //   padding: const EdgeInsets.all(3.0),
            //   decoration: new BoxDecoration(
            //     border: new Border.all(color: Colors.blueAccent)
            //   ),
            //   child: Text(responder['responsetime'] ?? '?',style: TextStyle(fontSize: ResponderTimeFontSize, color: Colors.white))),
            new Container(
              width: ResponderETAWidth,
              height: ResponderDefaultHeight,
              padding: const EdgeInsets.all(3.0),
              decoration: new BoxDecoration(
                border: new Border.all(color: Colors.blueAccent)
              ),
              child: Text(responder['eta'] ?? '?',
                      style: TextStyle(fontSize: ResponderDefaultFontSize, color: Colors.white)))
          ]
          )
        );
    }
  }

  bool isCalloutActive() {
    return (liveCallout != null && liveCallout.isNotEmpty && liveCallout.containsKey('id') && liveCallout['id'] != null);
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    final welcome = Padding(
      padding: EdgeInsets.all(0.0),
      child: FittedBox(
        fit: BoxFit.contain,
        child: Text(
          'Welcome: ' + userId ?? 'User',
          style: TextStyle(fontSize: 20.0, color: Colors.white),
        ),
      ),
    );

    final radio = new Container(
      child: FlatButton.icon(
        color: isRadioStreaming ? Colors.redAccent : Colors.lightGreenAccent,
        icon: isRadioStreaming ? Icon(Icons.pause) : Icon(Icons.play_arrow),
        label: isRadioStreaming ? Text('Pause Radio') : Text('Play Radio'),
        onPressed: isRadioStreaming ? stopRadioStream : startRadioStream
      ),
    );

    List<Widget> allWidgetParts = [];
    allWidgetParts.add(welcome);
    if(audioStreamRawUrl != null && audioStreamRawUrl.isNotEmpty) {
      allWidgetParts.add(radio);
    }

    if(isCalloutActive() == false) {
      final noCallouts = Padding(
        padding: EdgeInsets.all(0.0),
        child: FittedBox(
          fit: BoxFit.contain,
          child: Text(
            'Currently there are no active calls...',
            style: TextStyle(fontSize: 20.0, color: Colors.lightGreenAccent),
          ),
        ),
      );
      allWidgetParts.add(noCallouts);
    }
    else {
      List<Widget> calloutWidgetParts = [];
      
      buildCalloutHeader(calloutWidgetParts);
      allWidgetParts.addAll(calloutWidgetParts);
      
      List<Widget> responders = buildCalloutResponderHeader();
      List responses = buildResponderList();
      List<DropdownMenuItem<String>> statusList = buildStatusList();
      buildResponderRowList(responses, responders, statusList);

      allWidgetParts.addAll(responders);
    }

    final body = ModalProgressHUD(
      child: new Container(
        padding: EdgeInsets.all(2.0),
        decoration: BoxDecoration(
            gradient: LinearGradient(colors: [ Colors.black, Colors.black ]),
        ),
        child: 
        SizedBox.expand(
          child:Scrollbar(
            child: SingleChildScrollView(
            primary: true,
            child: Container(
              height: MediaQuery.of(context).size.height,
              decoration: new BoxDecoration(color: Colors.black),
              child: Column(
                children: allWidgetParts
              ))
            ))
      )),
      inAsyncCall: _inProgress
    );

    return body;
  }
}
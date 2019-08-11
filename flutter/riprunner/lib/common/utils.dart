import 'package:riprunner/auth/auth.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'dart:io';
import 'dart:async';

import '../app_constants.dart';
import 'chat_message.dart';
import 'data_container.dart';

enum APIRequestType {GET, POST}

class Utils {
  static Type typeOf<T>() => T;

  static Future<bool> hasConfigItem<T>(String keyName) async {
      final prefs = await SharedPreferences.getInstance();
      T value;
      if (T == typeOf<String>()) {
          value = prefs.getString(keyName) as T;
      } else if (T == typeOf<bool>()) {
          value = prefs.getBool(keyName) as T;
      }
      if (value == null) {
          return false;
      }
      return true;
    }

    static Future<T> getConfigItem<T>(String keyName) async {
      final prefs = await SharedPreferences.getInstance();
      T value;
      if (T == typeOf<String>()) {
          value = prefs.getString(keyName) as T;
      } else if (T == typeOf<bool>()) {
          value = prefs.getBool(keyName) as T;
      }
      return value;
    }

    static Future<void> setConfigItem<T>(String keyName, T value) async {
      final prefs = await SharedPreferences.getInstance();
      if (T == typeOf<String>()) {
          prefs.setString(keyName, value as String);
      } else if (T == typeOf<bool>()) {
          prefs.setBool(keyName, value as bool);
      }
    }

    static String addQueryParam(String url, String name, String value) {
      if (url.indexOf('?') == -1) {
        url += '?';
      }
      else {
        url += '&';
      }
      url += (name + '=' + value);
      return url;
    }

    static Future<String> injectJWTtoken(String url, bool handOffJWT) async {
      String token = await Utils.getConfigItem<String>(AppConstants.PROPERTY_AUTH);
      if(token != null && token.isNotEmpty) {
        if (url.indexOf('JWT_TOKEN') == -1) {
          url = addQueryParam(url, 'JWT_TOKEN', token);
          if (handOffJWT) {
            url = addQueryParam(url, 'JWT_TOKEN_HANDOFF', 'true');
          }
        }
      }
      return url;
    }

    static Future<void> injectJWTtokenHeader(HttpClientRequest request) async {
      String token = await Utils.getConfigItem<String>(AppConstants.PROPERTY_AUTH);
      if(token != null && token.isNotEmpty) {
        request.headers.set('jwt-token',token);
      }
    }

    static Future<void> injectSessionId(HttpClientRequest request) async {
      if(request != null) {
        Authentication.getSessionCookies().forEach((value) => request.headers.set(HttpHeaders.cookieHeader,value));
      }
    }

    static Future<String> apiRequest(String url, Map jsonMap, APIRequestType reqType, bool resetSession) async {
      try {
        HttpClient httpClient = new HttpClient();
        httpClient.badCertificateCallback = ((X509Certificate cert, String host, int port) => true);

        HttpClientRequest request;
        if(reqType == APIRequestType.GET) {
          request = await httpClient.getUrl(Uri.parse(url));
        }
        else if(reqType == APIRequestType.POST) {
          request = await httpClient.postUrl(Uri.parse(url));
        }

        if(resetSession == false) {
          await injectSessionId(request);
          await injectJWTtokenHeader(request);
        }

        if(jsonMap != null) {
          var body = json.encode(jsonMap);
          request.headers.set(HttpHeaders.contentTypeHeader, "application/json");
          request.headers.set(HttpHeaders.contentLengthHeader, body.length);
          //request.add(utf8.encode(body));
          request.write(body);
        }
        
        //String temp = request.headers.toString();
        HttpClientResponse response = await request.close();
        if(response.statusCode != HttpStatus.ok) {
          if(response.statusCode == HttpStatus.unauthorized) {
            Authentication.logout();
          }
          throw Exception('API response: ${response.statusCode} message: ${response.reasonPhrase}');
        }
        
        List<String> sessionIdCookies = response.headers[HttpHeaders.setCookieHeader];
        if(sessionIdCookies != null && sessionIdCookies.isNotEmpty) {
          Authentication.setSessionCookies(sessionIdCookies.map((value) {
            int index = value.indexOf(';');
            String sessionId = (index == -1) ? value : value.substring(0, index);
            return sessionId;
          }).toList());
        }
        
        String reply = await response.transform(utf8.decoder).join();
        httpClient.close();
        reply = Uri.decodeFull(reply);
        return reply;
      }
      catch(e) {
        print(e.toString());
        throw e;
      }
    }

	static bool hasDelimitedValueFromString(String rawValue, String regularExpression, int groupResultIndex, bool isMultiLine) {
		String result;
		if(rawValue != null && rawValue.isNotEmpty) {
			//Pattern p;
      RegExp exp;
			if(isMultiLine) {
				//exp = Pattern.compile(regularExpression,Pattern.MULTILINE);
        exp = new RegExp(regularExpression,multiLine: true);
			}
			else {
				//exp = Pattern.compile(regularExpression);
        exp = new RegExp(regularExpression,multiLine: false);
			}
			//Matcher m = p.matcher(rawValue);
			//if(m.find()) {
			//	result = m.group(groupResultIndex);
			//}
      if(exp.hasMatch(rawValue)) {
        RegExpMatch match = exp.firstMatch(rawValue);
        if(match != null && match.groupCount-1 >= groupResultIndex) {
          result = match.group(groupResultIndex);
        }
      }
		}

		return result != null;
	}



  // static String extractDelimitedValueFromString(String rawValue, String regularExpression, int groupResultIndex, bool isMultiLine) {
  //   String result = "";
  //   if(rawValue != null && rawValue.isNotEmpty) {
  //       //Pattern p;
  //       RegExp exp;
  //       if(isMultiLine) {
  //         //p = Pattern.compile(regularExpression,Pattern.MULTILINE);
  //         exp = new RegExp(regularExpression,multiLine: true);
  //       }
  //       else {
  //         //p = Pattern.compile(regularExpression);
  //         exp = new RegExp(regularExpression,multiLine: false);
  //       }
  //       if(exp.hasMatch(rawValue)) {
  //         RegExpMatch match = exp.firstMatch(rawValue);
  //         if(match != null && match.groupCount-1 >= groupResultIndex) {
  //           result = match.group(groupResultIndex);
  //         }
  //       }
  //   }
  //   return result;
  // }

	static Map<String, String> extractMapFromNameValueString(String str, String listDelimiter, String nameValueDelimiter) {
		//Map<String, String> map = Splitter.on( "::" ).withKeyValueSeparator( ':' ).split( calloutMsgRaw );
		Map<String, String> nameValueMap = new Map<String, String>();
		List<String> arr = str.split(listDelimiter);
		//for(String s : arr){
    for (String s in arr ?? []) {
			List<String> keyValuePair = s.split(nameValueDelimiter);
			nameValueMap.putIfAbsent(keyValuePair[0],() => keyValuePair[1]);
		}
		return nameValueMap;
	}

  static void processDeviceMsgTrigger(Map<String, dynamic> messageMap) {
    //String deviceMsgRaw = FireHallUtil.extractDelimitedValueFromString(
    //        msg, "DEVICE_MSG\\=(.*?)\\,", 1, true);
    //Map<String, String> calloutMsgMap = extractMapFromNameValueString(msg, ", ", "=");
    Map<String, String> calloutMsgMap = Map<String, dynamic>.from(messageMap);
    String deviceMsg = Uri.decodeQueryComponent(calloutMsgMap["DEVICE_MSG"]);
    if (deviceMsg != null && deviceMsg != "GCM_LOGINOK") {
        //AppMainActivity.this.processDeviceMsgTrigger(deviceMsg);
        // !! TODO
    }
  }

  static void processCalloutResponseTrigger(Map<String, dynamic> messageMap) {
//            String deviceMsgRaw = FireHallUtil.extractDelimitedValueFromString(
//                    msg, "CALLOUT_RESPONSE_MSG\\=(.*?)$", 1, true);
//
//            final String calloutMsg = URLDecoder.decode(deviceMsgRaw, "utf-8");
//            JSONObject json = new JSONObject(calloutMsg);

      //String calloutMsgBundle = msg;
      //String calloutMsgRaw = extractDelimitedValueFromString(
      //        calloutMsgBundle, "Bundle\\[\\{(.*?)\\}\\]", 1, true);
      //String calloutMsgRaw = calloutMsgBundle;

      //Map<String, String> calloutMsgMap = extractMapFromNameValueString(calloutMsgRaw, ", ", "=");
      Map<String, dynamic> calloutMsgMap = Map<String, dynamic>.from(messageMap);

      final String calloutMsg =  Uri.decodeQueryComponent(calloutMsgMap["CALLOUT_RESPONSE_MSG"]);
      String callout_id = Uri.decodeQueryComponent(calloutMsgMap["call-id"]);
      String callout_status = Uri.decodeQueryComponent(calloutMsgMap["user-status"]);
      String response_userid = Uri.decodeQueryComponent(calloutMsgMap["user-id"]);

      //AppMainActivity.this.processCalloutResponseTrigger(calloutMsg, callout_id,
      //        callout_status, response_userid);
      // !!! TODO
  }

  /**
   * Test data:
   * {call-key-id=57b11a0cabbe15.55047376, call-status=1, google.sent_time=1471240748576, call-type=STF2+-+Structure+Fire+-+Large, CALLOUT_MSG=911-Page%3A+Structure+Fire+-+Large%2C+5030+MEADOWVIEW+RD%2CSALMON+VALLEY%2C+BC+%40+2016-08-14+17%3A53%3A05, call-gps-long=-122.656740, call-address=5030+MEADOWVIEW+RD%2CSALMON+VALLEY%2C+BC, google.message_id=0:1471240748586485%c3eb0100f9fd7ecd, call-units=SALE12%2C+PILT12%2C+SALPMV11%2C+BEAE12%2C+PILPMV11%2C+BEAT12%2C+NEST11%2C+NESPMV11%2C+NESPMV12%2C+SALT11%2C+SALDUC1, call-id=101, call-gps-lat=54.096250, call-map-address=5030+MEADOWVIEW+RD%2CPRINCE+GEORGE%2C+BC, collapse_key=do_not_collapse}
   *
   * @param msg
   * @throws UnsupportedEncodingException
   * @throws JSONException
   */
  //void processCalloutTrigger(String msg)
  static void processCalloutTrigger(Map<String, dynamic> messageMap) {
      try {
          //String deviceMsgRaw = FireHallUtil.extractDelimitedValueFromString(
          //        msg, "CALLOUT_MSG\\=(.*?)(?:\\, collapse_key\\=|$|\\})", 1, true);
          //final String calloutMsg = "CALLOUT_MSG=" + URLDecoder.decode(deviceMsgRaw, "utf-8");
          //JSONObject json = new JSONObject(calloutMsg);
          //String calloutMsgBundle = msg;
          //String calloutMsgRaw = extractDelimitedValueFromString(calloutMsgBundle, "Bundle\\[\\{(.*?)\\}\\]", 1, true);
          //String calloutMsgRaw = calloutMsgBundle;

          //Map<String, String> map = Splitter.on( "::" ).withKeyValueSeparator( ':' ).split( calloutMsgRaw );
//                Map<String, String> calloutMsgMap = new HashMap<String, String>();
//                String[] arr = calloutMsgRaw.split(", ");
//                for(String s : arr){
//                    String[] keyValuePair = s.split("=");
//                    calloutMsgMap.put(keyValuePair[0],keyValuePair[1]);
//                }
          //Map<String, String> calloutMsgMap = extractMapFromNameValueString(calloutMsgRaw, ", ", "=");
          //Map<String, String> calloutMsgMap = calloutMsgRaw;
          Map<String, dynamic> calloutMsgMap = Map<String, dynamic>.from(messageMap);

          String gpsLatStr;
          String gpsLongStr;

          //intent.getExtras().getBundle("callout").getString("call-gps-lat")
          try {
              gpsLatStr = Uri.decodeQueryComponent(calloutMsgMap["call-gps-lat"]);
              gpsLongStr = Uri.decodeQueryComponent(calloutMsgMap["call-gps-long"]);
          }
          catch (e) {
              //Log.e(Utils.TAG, Utils.getLineNumber() + ": " + calloutMsg, e);

              throw new Exception("Could not parse callback GCM intent data: " + e);
          }

          String callKeyId = Uri.decodeQueryComponent(calloutMsgMap["call-key-id"]);
          if (callKeyId == null || callKeyId == "?") {
              callKeyId = "";
          }
          String callAddress = Uri.decodeQueryComponent(calloutMsgMap["call-address"]);
          if (callAddress == null || callAddress == "?") {
              callAddress = "";
          }
          String callMapAddress = Uri.decodeQueryComponent(calloutMsgMap["call-map-address"]);
          if (callMapAddress == null || callMapAddress == "?") {
              callMapAddress = "";
          }
          String callType = "?";
          if (calloutMsgMap.containsKey("call-type")) {
              callType = Uri.decodeQueryComponent(calloutMsgMap["call-type"]);
          }

          String callId = Uri.decodeQueryComponent(calloutMsgMap["call-id"]);
          String callUnits = Uri.decodeQueryComponent(calloutMsgMap["call-units"]);
          String callStatus = Uri.decodeQueryComponent(calloutMsgMap["call-status"]);
          String callMsg = Uri.decodeQueryComponent(calloutMsgMap["CALLOUT_MSG"]);
          // AppMainActivity.this.processCalloutTrigger(
          //         callId,
          //         callKeyId,
          //         callType,
          //         gpsLatStr, gpsLongStr,
          //         callAddress,
          //         callMapAddress,
          //         callUnits,
          //         callStatus,
          //         callMsg);
          // !!! TODO
      } catch (e) {
          // should never happen
          print("Rip Runner Error"+ e.toString());
          throw new Exception("Error processing callout trigger: " + e);
      }
  }

  static void processAdminMsgTrigger(Map<String, dynamic> messageMap, DataContainer container) {
    // runOnUiThread(new Runnable() {
    //     public void run() {
    //         mDisplay = (TextView) findViewById(R.id.display);
    //         mDisplay.append("\n" + getResources().getString(R.string.Message_from_admin_prefix) + adminMsg);
    //         scrollToBottom(mDisplayScroll, mDisplay);

    //         Uri notification = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION);
    //         Ringtone r = RingtoneManager.getRingtone(getApplicationContext(), notification);
    //         r.play();
    //     }
    // });
    // !!! TODO
    // Map<String, dynamic> msg = {};
    // msg['idFrom'] = 'Admin';
    // msg['type'] = 0;
    // msg['content'] = messageMap['ADMIN_MSG'];
    // msg['timestamp'] = DateTime.now().millisecondsSinceEpoch.toString();
    //container.getDataFromMap('CHAT_MESSAGES').add(msg);
        container.getDataFromMap('CHAT_MESSAGES').add(
       ChatMessage('Administrator', '', Uri.decodeQueryComponent(messageMap['ADMIN_MSG']), ChatMessageType.admin, DateTime.now()));
}

}
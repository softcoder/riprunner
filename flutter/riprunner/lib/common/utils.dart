import 'dart:ui';

import 'package:riprunner/auth/auth.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:logger/logger.dart';

import '../app_constants.dart';
import 'chat_message.dart';
import 'data_container.dart';

enum APIRequestType {GET, POST}

class Utils {
  static Logger logger = Logger();
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
      RegExp exp;
			if(isMultiLine) {
        exp = new RegExp(regularExpression,multiLine: true);
			}
			else {
        exp = new RegExp(regularExpression,multiLine: false);
			}
      if(exp.hasMatch(rawValue)) {
        RegExpMatch match = exp.firstMatch(rawValue);
        if(match != null && match.groupCount-1 >= groupResultIndex) {
          result = match.group(groupResultIndex);
        }
      }
		}

		return result != null;
	}

	static Map<String, String> extractMapFromNameValueString(String str, String listDelimiter, String nameValueDelimiter) {
		Map<String, String> nameValueMap = new Map<String, String>();
		List<String> arr = str.split(listDelimiter);
    for (String s in arr ?? []) {
			List<String> keyValuePair = s.split(nameValueDelimiter);
			nameValueMap.putIfAbsent(keyValuePair[0],() => keyValuePair[1]);
		}
		return nameValueMap;
	}

  static void processDeviceMsgTrigger(Map<String, dynamic> messageMap) {
    Map<String, String> calloutMsgMap = Map<String, dynamic>.from(messageMap);
    String deviceMsg = Uri.decodeQueryComponent(calloutMsgMap["DEVICE_MSG"]);
    if (deviceMsg != null && deviceMsg != "FCM_LOGINOK") {
        //AppMainActivity.this.processDeviceMsgTrigger(deviceMsg);
        print("In processDeviceMsgTrigger deviceMsg = $deviceMsg");
    }
  }

  static void processCalloutResponseTrigger(Map<String, dynamic> messageMap) {
      Map<String, dynamic> calloutMsgMap = Map<String, dynamic>.from(messageMap);

      final String calloutMsg = Uri.decodeQueryComponent(calloutMsgMap["CALLOUT_RESPONSE_MSG"]);
      String calloutId = Uri.decodeQueryComponent(calloutMsgMap["call-id"]);
      String calloutStatus = Uri.decodeQueryComponent(calloutMsgMap["user-status"]);
      String responseUserid = Uri.decodeQueryComponent(calloutMsgMap["user-id"]);

      print("In processCalloutResponseTrigger calloutMsg = $calloutMsg callout_id = $calloutId callout_status = $calloutStatus response_userid = $responseUserid");
  }

  static void processCalloutTrigger(Map<String, dynamic> messageMap) {
      try {
          Map<String, dynamic> calloutMsgMap = Map<String, dynamic>.from(messageMap);
          String callKeyId = Uri.decodeQueryComponent(calloutMsgMap["call-key-id"]);
          if (callKeyId == null || callKeyId == "?") {
              callKeyId = "";
          }

          String callId = Uri.decodeQueryComponent(calloutMsgMap["call-id"]);
          String callMsg = Uri.decodeQueryComponent(calloutMsgMap["CALLOUT_MSG"]);

          print("In processCalloutTrigger callId = $callId callKeyId = $callKeyId callMsg = $callMsg");
      } 
      catch (e) {
           print("Rip Runner Error: "+ e.toString());
          throw new Exception("Error processing callout trigger: " + e);
      }
  }

  static void processAdminMsgTrigger(Map<String, dynamic> messageMap, DataContainer container) {
    AppLifecycleState state = container.getDataFromMap('APP_STATE');
    if(state.index != AppLifecycleState.resumed.index) {

    }
    container.getDataFromMap('CHAT_MESSAGES').add(
       ChatMessage('Administrator', '', Uri.decodeQueryComponent(messageMap['ADMIN_MSG']), ChatMessageType.admin, DateTime.now()));
  }

  static Logger getLogger() {
    return logger;
  }
}
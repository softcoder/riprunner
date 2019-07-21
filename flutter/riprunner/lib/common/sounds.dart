
import 'dart:io';

import 'package:flutter/services.dart';
import 'package:path_provider/path_provider.dart';
import 'package:audioplayer/audioplayer.dart';

enum ResourceType {
  LocalFile,
  LocalAsset,
  URL,
}

class SoundUtils {
    static void playSound(AudioPlayer audioPlayer, String resource, ResourceType type) {
      if(type == ResourceType.LocalAsset) {
        rootBundle.load(resource).then((bytes) {
          getApplicationDocumentsDirectory().then((tempDir) {
            
            File tempFile = File('${tempDir.path}/_temp1.mp3');
            tempFile.writeAsBytes(bytes.buffer.asUint8List(), flush: true).then((x) {
              String sound = tempFile.uri.toString();
              audioPlayer.stop();
              audioPlayer.play(sound, isLocal: true);
            });
          });
        });
      }
      else if(type == ResourceType.URL) {
        HttpClient client = new HttpClient();
        client.badCertificateCallback = ((X509Certificate cert, String host, int port) => true);
        client.getUrl(Uri.parse(resource)).then((HttpClientRequest request) {
            return request.close();
          }).then((HttpClientResponse response) {
            var _downloadData = List<int>();
            response.listen((d) => _downloadData.addAll(d),
              onDone: () {
                getApplicationDocumentsDirectory().then((tempDir) {
                  File tempFile = File('${tempDir.path}/_temp2.mp3');
                  tempFile.writeAsBytes(_downloadData, flush: true).then((x) {
                    String sound = tempFile.uri.toString();
                    audioPlayer.stop();
                    audioPlayer.play(sound, isLocal: true);
                  });
                });
              }
            );
          });
      }
      else {
        audioPlayer.stop();
        audioPlayer.play(resource, isLocal: true);
      }
    }
}
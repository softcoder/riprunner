
enum ChatMessageType { peer, group, admin, system }

class ChatMessage {
  final String peerId;
  final String peerAvatar;
  final String text;
  final ChatMessageType type;
  final DateTime timestamp;

  ChatMessage(this.peerId, this.peerAvatar, this.text, this.type, this.timestamp);
}
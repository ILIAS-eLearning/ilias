const Container = require('../AppContainer');
const TextMessage = require('../Model/Messages/TextMessage');
const TargetMessage = require('../Model/Messages/TargetMessage');

module.exports = function (data, roomId) {
	const serverRoomId = Container.createServerRoomId(roomId);
	const namespace = Container.getNamespace(this.nsp.name);

	Container.getLogger().info('Message send to room %s of namespace %s', serverRoomId, namespace.getName());
	if (typeof this.subscriber === "undefined") {
		Container.getLogger().error("Missing subscriber, don't process message");
		return;
	}

	const subscriber = {id: this.subscriber.getId(), username: this.subscriber.getName()};
	let message = {};

	if (data.target !== undefined) {
		message = TargetMessage.create(data.content, roomId, subscriber, data.format, data.target);

		if (message.target.public) {
			namespace.getIO().in(serverRoomId).emit('message', message);
		} else {
			const target = namespace.getSubscriber(message.target.id);
			const from = namespace.getSubscriber(message.from.id);

			const emitMessage = function (socketId) {
				namespace.getIO().to(socketId).emit('message', message);
			};

			from.getSocketIds().forEach(emitMessage);
			target.getSocketIds().forEach(emitMessage);
		}
	} else {
		message = TextMessage.create(data.content, roomId, subscriber, data.format);
		this.nsp.in(serverRoomId).emit('message', message);
	}

	namespace.getDatabase().persistMessage(message);
};

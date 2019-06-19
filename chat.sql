/*
 Navicat Premium Data Transfer

 Source Server         : homestead
 Source Server Type    : MySQL
 Source Server Version : 50725
 Source Host           : 127.0.0.1:33060
 Source Schema         : chat

 Target Server Type    : MySQL
 Target Server Version : 50725
 File Encoding         : 65001

 Date: 19/06/2019 14:38:34
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for chat_record
-- ----------------------------
DROP TABLE IF EXISTS `chat_record`;
CREATE TABLE `chat_record`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL DEFAULT 0 COMMENT '是群聊消息记录的话 此id为0',
  `group_id` int(11) NOT NULL DEFAULT 0 COMMENT '如果不为0说明是群聊',
  `content` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 91 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '聊天记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chat_record
-- ----------------------------
INSERT INTO `chat_record` VALUES (80, 10014, 10013, 0, '11', 1560922127);
INSERT INTO `chat_record` VALUES (81, 10013, 10014, 0, ' q', 1560922140);
INSERT INTO `chat_record` VALUES (82, 10014, 10013, 0, 's', 1560922159);
INSERT INTO `chat_record` VALUES (83, 10014, 10013, 0, 'assasasa', 1560922177);
INSERT INTO `chat_record` VALUES (84, 10013, 10014, 0, 'face[晕] ', 1560922183);
INSERT INTO `chat_record` VALUES (85, 10014, 10013, 0, '2w2', 1560922227);
INSERT INTO `chat_record` VALUES (86, 10013, 10014, 0, '32', 1560922244);
INSERT INTO `chat_record` VALUES (87, 10014, 10013, 0, 'www', 1560922260);
INSERT INTO `chat_record` VALUES (88, 10013, 10014, 0, '12312', 1560922518);
INSERT INTO `chat_record` VALUES (89, 10013, 10014, 0, '213', 1560922524);
INSERT INTO `chat_record` VALUES (90, 10013, 0, 10014, '1', 1560923445);

-- ----------------------------
-- Table structure for friend
-- ----------------------------
DROP TABLE IF EXISTS `friend`;
CREATE TABLE `friend`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `friend_group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 86 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '好友表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of friend
-- ----------------------------
INSERT INTO `friend` VALUES (84, 10013, 10014, 10);
INSERT INTO `friend` VALUES (85, 10014, 10013, 11);

-- ----------------------------
-- Table structure for friend_group
-- ----------------------------
DROP TABLE IF EXISTS `friend_group`;
CREATE TABLE `friend_group`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `groupname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of friend_group
-- ----------------------------
INSERT INTO `friend_group` VALUES (10, 10013, '默认分组');
INSERT INTO `friend_group` VALUES (11, 10014, '默认分组');
INSERT INTO `friend_group` VALUES (12, 10015, '默认分组');

-- ----------------------------
-- Table structure for group
-- ----------------------------
DROP TABLE IF EXISTS `group`;
CREATE TABLE `group`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '群组所属用户id,群主',
  `groupname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '群名',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10016 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '群组' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of group
-- ----------------------------
INSERT INTO `group` VALUES (10014, 10013, '测试', '/Static/upload/5d09c956c0ccctimg.jpg');
INSERT INTO `group` VALUES (10015, 10014, '吹水群', '/Static/upload/5d09cdca1dffb3-1G123203S6-50.jpg');

-- ----------------------------
-- Table structure for group_member
-- ----------------------------
DROP TABLE IF EXISTS `group_member`;
CREATE TABLE `group_member`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 46 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of group_member
-- ----------------------------
INSERT INTO `group_member` VALUES (39, 10001, 10013);
INSERT INTO `group_member` VALUES (40, 10001, 10014);
INSERT INTO `group_member` VALUES (41, 10014, 10013);
INSERT INTO `group_member` VALUES (42, 10014, 10014);
INSERT INTO `group_member` VALUES (43, 10015, 10014);
INSERT INTO `group_member` VALUES (44, 10015, 10013);
INSERT INTO `group_member` VALUES (45, 10001, 10015);

-- ----------------------------
-- Table structure for offline_message
-- ----------------------------
DROP TABLE IF EXISTS `offline_message`;
CREATE TABLE `offline_message`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `data` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未发送 1已发送',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '离线消息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for system_message
-- ----------------------------
DROP TABLE IF EXISTS `system_message`;
CREATE TABLE `system_message`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '接收用户id',
  `from_id` int(11) NOT NULL COMMENT '来源相关用户id',
  `group_id` int(11) NOT NULL DEFAULT 0,
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '添加好友附言',
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0好友请求 1请求结果通知',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未处理 1同意 2拒绝',
  `read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未读 1已读，用来显示消息盒子数量',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 88 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统消息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of system_message
-- ----------------------------
INSERT INTO `system_message` VALUES (66, 10001, 10005, 4, '', 0, 2, 1, 1560917188);
INSERT INTO `system_message` VALUES (67, 10005, 10001, 0, '', 1, 2, 1, 1560917196);
INSERT INTO `system_message` VALUES (68, 10001, 10005, 4, '', 0, 1, 1, 1560917221);
INSERT INTO `system_message` VALUES (69, 10005, 10001, 0, '', 1, 1, 1, 1560917226);
INSERT INTO `system_message` VALUES (70, 10005, 10001, 3, '', 0, 1, 1, 1560917363);
INSERT INTO `system_message` VALUES (71, 10001, 10005, 0, '', 1, 1, 1, 1560917371);
INSERT INTO `system_message` VALUES (72, 10005, 10001, 3, '', 0, 1, 1, 1560917512);
INSERT INTO `system_message` VALUES (73, 10001, 10005, 0, '', 1, 1, 1, 1560917557);
INSERT INTO `system_message` VALUES (74, 10005, 10001, 3, '', 0, 1, 1, 1560917998);
INSERT INTO `system_message` VALUES (75, 10001, 10005, 0, '', 1, 1, 1, 1560918012);
INSERT INTO `system_message` VALUES (76, 10005, 10001, 3, '', 0, 2, 1, 1560918338);
INSERT INTO `system_message` VALUES (77, 10001, 10005, 0, '', 1, 2, 1, 1560918348);
INSERT INTO `system_message` VALUES (78, 10005, 10001, 3, '', 0, 2, 1, 1560918411);
INSERT INTO `system_message` VALUES (79, 10001, 10005, 0, '', 1, 2, 1, 1560918430);
INSERT INTO `system_message` VALUES (80, 10005, 10001, 3, '', 0, 2, 1, 1560918514);
INSERT INTO `system_message` VALUES (81, 10001, 10005, 0, '', 1, 2, 1, 1560918518);
INSERT INTO `system_message` VALUES (82, 10005, 10001, 3, '', 0, 2, 1, 1560918600);
INSERT INTO `system_message` VALUES (83, 10001, 10005, 0, '', 1, 2, 1, 1560918612);
INSERT INTO `system_message` VALUES (84, 10001, 10005, 4, '', 0, 1, 1, 1560920590);
INSERT INTO `system_message` VALUES (85, 10005, 10001, 0, '', 1, 1, 1, 1560920620);
INSERT INTO `system_message` VALUES (86, 10013, 10014, 11, '', 0, 1, 1, 1560922101);
INSERT INTO `system_message` VALUES (87, 10014, 10013, 0, '', 1, 1, 1, 1560922107);

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '头像',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '昵称',
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sign` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '签名',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'online' COMMENT 'online在线 hide隐身 offline离线',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10016 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES (10013, '/Static/upload/5d09c6a5b6def15337177846531748ac16fb.jpg', 'jin', 'jin', '$2y$10$cS3rfhqaHeOfVfkAFd1MnuzslrWhihLP/awA07hQPOfUqixV0yp1q', '我是jin', 'online');
INSERT INTO `user` VALUES (10014, '/Static/upload/5d09c7da7bc97tx20218.jpg', '前端工程师', 'test1', '$2y$10$m.4h0u0L56G2Oje6ZnNMyulR.9DMvPT4VkXo2RcTHr8NEqVa.cq8C', '我是前端工程师', 'offline');
INSERT INTO `user` VALUES (10015, '/Static/upload/5d09ceac4de6312262La2-0.jpg', 'php工程师', 'test2', '$2y$10$ueMA2hy8x.Tan3nxZlpCmugUcViGCaV/cAeA4V5YX.yU.1kCtAtzq', '123', 'offline');

SET FOREIGN_KEY_CHECKS = 1;

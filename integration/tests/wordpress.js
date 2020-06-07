var xmlrpc = require('xmlrpc');
var Browser = require('./Browser');
var config = require('./config');
var client = xmlrpc.createClient({ host: config.HOST, port: config.PORT, path: config.XMLRPC_PATH });

//
// see: https://codex.wordpress.org/XML-RPC_WordPress_API/Posts
//

function getPost(postId) {
    return new Promise((resolve, reject) => {
        client.methodCall('wp.getPost', [config.BLOG_ID, config.USER, config.PASS, postId], (err, post) => {
            err ? reject(err) : resolve(post);
        });
    })
}

function newPost(title, content) {
    return new Promise((resolve, reject) => {
        client.methodCall('wp.newPost', [config.BLOG_ID, config.USER, config.PASS, {
            post_title: title,
            post_content: content,
            post_status: 'publish',
            comment_status: 'open'
        }], (err, postId) => {
            err ? reject(err) : resolve(postId);
        });
    })
}

function deletePost(postId) {
    return new Promise((resolve, reject) => {
        client.methodCall('wp.deletePost', [config.BLOG_ID, config.USER, config.PASS, postId], (err) => {
            err ? reject(err) : resolve();
        });
    });
}

function newComment(postId, content) {
    return new Promise((resolve, reject) => {
        client.methodCall('wp.newComment', [config.BLOG_ID, config.USER, config.PASS, postId, {
            content: content
        }], (err, commentId) => {
            err ? reject(err) : resolve(commentId);
        });
    });
}

function visitPost(postId) {
    const browser = new Browser();
    return getPost(postId).then(post => browser.visit(post.link).then(() => post.link))
}

module.exports = {
    getPost: getPost,
    newPost: newPost,
    deletePost: deletePost,
    newComment: newComment,
    visitPost: visitPost
};

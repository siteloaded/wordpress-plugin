const Browser = require('./browser');
const wp = require('./wordpress');

describe('With an existing cached page', function() {

    const comment = 'comment:' + Math.random().toString(36).substring(2);
    var postId;
    var postUrl;

    before(function() {
        return wp.newPost('Post for comment ' + new Date().toString(), 'empty content')
            .then(id => postId = id)
            .then(id => wp.visitPost(id))
            .then(url => postUrl = url)
    });

    after(function() {
        return wp.deletePost(postId);
    });

    describe('When a comment is being made', function() {
        const browser = new Browser();

        before(function() {
            return wp.newComment(postId, comment)
                .then(() => browser.visit(postUrl))
        });

        it('should be visible on the next page load', function() {
            browser.assert.text('.comment-content p', comment);
        });
    });
});

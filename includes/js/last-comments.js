jQuery(document).ready(function($){

var Comentario = Backbone.Model.extend({
    defaults: {
        id: "",
        comentario: "",
        autor: "",
        data: ""
    }
});

var ComentarioCollection = Backbone.Collection.extend({model: Comentario});

var Secao = Backbone.Model.extend({
    defaults: {
        id: "",
        secao: "",
        comentarios: new ComentarioCollection
    }
});

var ComentarioView = Backbone.View.extend({
    tagName: 'li',
    template: '',
    model: Comentario,

    initialize: function() {
        this.template = _.template($('#comentarioItem').html());
    },

    render: function() {
        var _this = this;
        var currComment = this.$el.find('#comment-'+this.model.attributes.id);

        if(currComment.length == 0){
            $(this.template(this.model.attributes)).hide().prependTo(this.$el).fadeIn(1000);
        }

        var quantComment = _this.$el.find('.list-group-item:visible').length;
        if(quantComment > 3){
            _this.$el.find('.list-group-item:visible').last().fadeOut(500);
        }

        return this;
    }
});

var SecaoView = Backbone.View.extend({
    tagName: 'div',
    template: '',
    model: Secao,

    initialize: function() {
        this.template = _.template($('#secaoItem').html());
    },

    render: function() {
        var _this = this;
        var quantSection = _this.$el.find('.comments-col:visible').length;
        var currentSection = _this.$el.find('#section-'+_this.model.attributes.id);

        if(currentSection.length == 0){
            if(quantSection >= 3){
                _this.$el.find('.comments-col:visible').first().hide(500);
            }
            $(this.template(this.model.attributes)).hide().appendTo(this.$el).fadeIn(1000);
        }

        this.model.attributes.comentarios.forEach(function( item ){
            var sectionArea = _this.$el.find('#section-' + _this.model.attributes.id);
            var commentArea = sectionArea.find('.list-group');
            comentario = new ComentarioView({model: item, el: commentArea});
            comentario.render();
            sectionArea.find('a').attr('href', commentFrontParams.post_url + '#commentable-section-' + _this.model.attributes.id);
        });

        return this;
    }
});

var SecaoCollection = Backbone.Collection.extend({
    model : Secao,
    url : 'wp-admin/admin-ajax.php',
    parse: function(response) {
        customResponse = [];
        if(!response.data.error_message){
            response.data.forEach(function(item){
                comments = [];
                item.comments.forEach(function( comment){
                    comments.push(new Comentario({id: comment.id, comentario: comment.comment_text, autor: comment.author, data: comment.date}));
                });
                customResponse.push({
                    id: item.section_id,
                    secao : item.section_text,
                    comentarios: comments
                });
            });
        }
        return customResponse;
    }
});

var secaoCollection = new SecaoCollection();
    var carregaSecao = function(){
        secaoCollection.fetch({
            data: {
                last_comments_nonce: commentFrontParams.nonce,
                post_id: commentFrontParams.post_id,
                action : 'last_comments_callback',
            },
            type: 'POST',
            update: true,
            success: function(){

                var className = "three-col";
                var numCols = secaoCollection.models.length;
                switch (numCols){
                    case 1 :
                        className = "one-col"
                        break;
                    case 2 :
                        className = "two-col";
                        break;
                }
                $('.comments-main div').first().removeClass().addClass(className);

                secaoCollection.models.forEach(function(secao){
                    secaoView = new SecaoView({model: secao, el: $('.comments-main div').first()});
                    secaoView.render();
                });
            }
        });
    };
    carregaSecao();
    setInterval(carregaSecao, commentFrontParams.delay * 1000);

});

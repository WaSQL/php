<!DOCTYPE html>
<html><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
        
    <title>Javascript for "Add to Home Screen" on iPhone? - Stack Overflow</title>
    <link rel="shortcut icon" href="http://cdn.sstatic.net/stackoverflow/img/favicon.ico">
    <link rel="apple-touch-icon image_src" href="http://cdn.sstatic.net/stackoverflow/img/apple-touch-icon.png">
    <link rel="search" type="application/opensearchdescription+xml" title="Stack Overflow" href="http://stackoverflow.com/opensearch.xml">
    
    <script src="mobile_bookmark_files/adzerk1_2_4_43adzerk2_2_17_45adzerk3_2_4_44" async="" type="text/javascript"></script><script src="mobile_bookmark_files/ga.js" async="" type="text/javascript"></script><script src="mobile_bookmark_files/quant.js" async="" type="text/javascript"></script><script type="text/javascript" src="mobile_bookmark_files/jquery.js"></script>
    <script type="text/javascript" src="mobile_bookmark_files/stub.js"></script>
    <link rel="stylesheet" type="text/css" href="mobile_bookmark_files/all.css">
    
    <link rel="canonical" href="http://stackoverflow.com/questions/1141979/javascript-for-add-to-home-screen-on-iphone">
    <link rel="alternate" type="application/atom+xml" title="Feed for question 'Javascript for &quot;Add to Home Screen&quot; on iPhone?'" href="http://stackoverflow.com/feeds/question/1141979">
    <script type="text/javascript">
        
        StackExchange.ready(function () {
            StackExchange.using("postValidation", function () {
                StackExchange.postValidation.initOnBlurAndSubmit($('#post-form'), 2, 'answer');
            });

            
            StackExchange.question.init({showAnswerHelp:true,totalCommentCount:0,shownCommentCount:0,highlightColor:'#F4A83D',backgroundColor:'#FFF',questionId:1141979});

            styleCode();

                StackExchange.realtime.subscribeToQuestion('1', '1141979');
            
                
        });
    </script>


    <script type="text/javascript">
        StackExchange.init({"stackAuthUrl":"https://stackauth.com","serverTime":1372434479,"styleCode":true,"enableUserHovercards":true,"site":{"name":"Stack Overflow","description":"Q&A for professional and enthusiast programmers","isNoticesTabEnabled":true,"recaptchaPublicKey":"6LdchgIAAAAAAJwGpIzRQSOFaO0pU6s44Xt8aTwc","enableSocialMediaInSharePopup":true},"user":{"fkey":"3ce03f68a6e52f91f259b97722e6351e","isAnonymous":true}});
        StackExchange.using.setCacheBreakers({"js/prettify-full.js":"6c261bebf56a","js/moderator.js":"82e0bdb93733","js/full-anon.js":"761e0ff892e6","js/full.js":"617cceaf451d","js/wmd.js":"2f79c03846d5","js/third-party/jquery.autocomplete.min.js":"e5f01e97f7c3","js/mobile.js":"e8e23ad37820","js/help.js":"6e6623243cf6","js/tageditor.js":"450c9e8426fc","js/tageditornew.js":"b6c68ad4c7dd","js/inline-tag-editing.js":"8e84e8a137f7","js/revisions.js":"7273bb714bba","js/review.js":"aa4e9e92f60d","js/tagsuggestions.js":"aa48ef6154df","js/post-validation.js":"bb996020492a","js/explore-qlist.js":"1c5bbd79b562"});
        
    </script>
    <script type="text/javascript">
        StackExchange.using("gps", function() {
             StackExchange.gps.init(true);
        });
    </script>
    
        <script type="text/javascript">
            StackExchange.ready(function () {
                $('#nav-tour').click(function () {
                    StackExchange.using("gps", function() {
                        StackExchange.gps.track("aboutpage.click", { aboutclick_location: "headermain" }, true);
                    });
                });
            });
        </script>
<script src="mobile_bookmark_files/full-anon.js" type="text/javascript" async=""></script><script src="mobile_bookmark_files/post-validation.js" type="text/javascript" async=""></script><script src="mobile_bookmark_files/adFeedback.js" type="text/javascript"></script><link href="mobile_bookmark_files/adFeedback.css" rel="stylesheet"><link rel="stylesheet" type="text/css" href="mobile_bookmark_files/sidebar.css"></head>
<body class="question-page">
    <noscript><div id="noscript-padding"></div></noscript>
    <div id="notify-container"></div>
    <div id="overlay-header"></div>
    <div id="custom-header"></div>

    <div class="container">
        <div id="header" class="headeranon">
            <div id="portalLink">
                <a class="genu" onclick="StackExchange.ready(function(){genuwine.click();});return false;">Stack Exchange</a>
            </div>
            <div id="topbar">
                <div id="hlinks">
                    
<span id="hlinks-user"></span>
<span id="hlinks-nav">                        <a href="http://stackoverflow.com/users/login?returnurl=%2fquestions%2f1141979%2fjavascript-for-add-to-home-screen-on-iphone">sign up</a>

 <span class="lsep">|</span>
                    <a href="http://stackoverflow.com/users/login?returnurl=%2fquestions%2f1141979%2fjavascript-for-add-to-home-screen-on-iphone">log in</a>

 <span class="lsep">|</span>
                    <a href="http://careers.stackoverflow.com/">careers 2.0</a>

 <span class="lsep">|</span>
</span>
<span id="hlinks-custom"></span>
                </div>
                <div id="hsearch">
                    <form id="search" action="/search" method="get" autocomplete="off">
                        <div>
                            <input autocomplete="off" name="q" class="textbox" placeholder="search" tabindex="1" maxlength="240" size="28" type="text">
                        </div>
                    </form>
                </div>
            </div>
            <br class="cbt">
            <div id="hlogo">
                <a href="http://stackoverflow.com/">
                    Stack Overflow
                </a>
            </div>
            <div id="hmenus">
                <div class="nav mainnavs mainnavsanon">
                    <ul>
                            <li class="youarehere"><a id="nav-questions" href="http://stackoverflow.com/questions">Questions</a></li>
                            <li><a id="nav-tags" href="http://stackoverflow.com/tags">Tags</a></li>
                            <li><a id="nav-tour" href="http://stackoverflow.com/about">Tour</a></li>
                            <li><a id="nav-users" href="http://stackoverflow.com/users">Users</a></li>
                    </ul>
                </div>
                <div class="nav askquestion">
                    <ul>
                        <li>
                            <a id="nav-askquestion" href="http://stackoverflow.com/questions/ask">Ask Question</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        



        <div id="content">
            

<div itemscope="" itemtype="http://schema.org/Article">
<link itemprop="image" href="http://cdn.sstatic.net/stackoverflow/img/apple-touch-icon.png">
<!--googleoff: snippet-->
<div id="herobox-mini">
    <div id="hero-content">
        <span id="controls">
            <a href="http://stackoverflow.com/about" id="tell-me-more" class="button">Tell me more</a>
            <span id="close"><a title="click to dismiss">×</a></span>
        </span>
        <div id="blurb">
            <span id="site-name">Stack Overflow</span> is a question and answer site for 
            professional and enthusiast programmers. It's 100% free, no registration required.
        </div>        
    </div>
    <script>
        $('#tell-me-more').click(function () {
            var clickSource = $("body").attr("class") + '-mini';
            if ($("body").hasClass("questions-page")) {
                clickSource = 'questionpagemini';
            }
            if ($("body").hasClass("home-page")) {
                clickSource = 'homepagemini';
            }

            StackExchange.using("gps", function () {
                StackExchange.gps.track("aboutpage.click", { aboutclick_location: clickSource } , true);
            });
        });
        $('#herobox-mini #close').click(function () {
            StackExchange.using("gps", function () {
                StackExchange.gps.track("hero.action", { hero_action_type: "close" }, true);
            });
            $.cookie("hero", "none", { path: "/" });
            var $hero = $("#herobox-mini");
            $hero.slideUp('fast', function () { $hero.remove(); });
            return false;
        });
    </script>
</div>
<!--googleon: snippet-->
<div id="question-header">
    <h1 itemprop="name"><a href="http://stackoverflow.com/questions/1141979/javascript-for-add-to-home-screen-on-iphone" class="question-hyperlink">Javascript for “Add to Home Screen” on iPhone?</a></h1>
</div>
<div id="mainbar">



<div class="question" data-questionid="1141979" id="question">
    
                <div class="everyonelovesstackoverflow adzerk-vote" id="adzerk1">
            <iframe id="ados_frame_adzerk1_38197" frameborder="0" height="90" scrolling="no" width="728"></iframe><img src="mobile_bookmark_files/i_002.gif" border="0" height="0px" width="0px"><div class="adzerk-vote-controls" style="display:none;"><div class="adzerk-vote-option adzerk-vote-up"><div class="adzerk-vote-icon"></div></div><div class="adzerk-vote-option adzerk-vote-down"><div class="adzerk-vote-icon"></div></div></div><div class="adzerk-vote-survey" style="display:none;"><form><span>No problem. We won't show you that ad again. Why didn't you like it?</span><ul><li><label><input value="12" name="downvoteReason" type="radio">Uninteresting</label></li><li><label><input value="13" name="downvoteReason" type="radio">Misleading</label></li><li><label><input value="14" name="downvoteReason" type="radio">Offensive</label></li><li><label><input value="15" name="downvoteReason" type="radio">Repetitive</label></li></ul><a href="#" class="adzerk-vote-cancel">Oops! I didn't mean to do this.</a></form></div></div>


    <table>
        <tbody><tr>
            <td class="votecell">
                

<div class="vote">
    <input value="1141979" type="hidden">
    <a class="vote-up-off" title="This question shows research effort; it is useful and clear">up vote</a>
    <span class="vote-count-post ">48</span>
    <a class="vote-down-off" title="This question does not show any research effort; it is unclear or not useful">down vote</a>
    
    <a class="star-off" href="#" title="This is a favorite question (click again to undo)">favorite</a>
    <div class="favoritecount"><b>25</b></div>   

</div>

            </td>
            
<td class="postcell">
<div>
    <div class="post-text" itemprop="description">
        <p>Is it possible to use Javascript to emulate the Add to Home Screen option in Mobile Safari's bookmark menu?</p>

<p>Something similar to IE's <code>window.external.AddFavorite(location.href, document.title);</code> possibly?</p>

    </div>
    <div class="post-taglist">
        <a href="http://stackoverflow.com/questions/tagged/javascript" class="post-tag" title="show questions tagged 'javascript'" rel="tag">javascript</a> <a href="http://stackoverflow.com/questions/tagged/iphone" class="post-tag" title="show questions tagged 'iphone'" rel="tag">iphone</a> <a href="http://stackoverflow.com/questions/tagged/mobile-safari" class="post-tag" title="show questions tagged 'mobile-safari'" rel="tag">mobile-safari</a> <a href="http://stackoverflow.com/questions/tagged/bookmarks" class="post-tag" title="show questions tagged 'bookmarks'" rel="tag">bookmarks</a> <a href="http://stackoverflow.com/questions/tagged/home-screen" class="post-tag" title="show questions tagged 'home-screen'" rel="tag">home-screen</a> 
    </div>
    <table class="fw">
    <tbody><tr>
    <td class="vt">










<div class="post-menu"><a href="http://stackoverflow.com/q/1141979" title="short permalink to this question" class="short-link" id="link-post-1141979">share</a><span class="lsep">|</span><a href="http://stackoverflow.com/posts/1141979/edit" class="suggest-edit-post" title="">improve this question</a></div>        
    </td>
    <td class="post-signature owner">
        

    <div class="user-info ">
        <div class="user-action-time">
                                    asked <span title="2009-07-17 08:13:49Z" class="relativetime">Jul 17 '09 at 8:13</span>
        </div>
        <div class="user-gravatar32">
            <a href="http://stackoverflow.com/users/126329/kerrick"><div class=""><img src="mobile_bookmark_files/2956e2cd2664630aa968b92bbb645f2f.jpg" alt="" height="32" width="32"></div></a>
        </div>
        <div class="user-details">
            <a href="http://stackoverflow.com/users/126329/kerrick">Kerrick</a><br>
            <span class="reputation-score" title="reputation score" dir="ltr">1,779</span><span title="4 gold badges"><span class="badge1"></span><span class="badgecount">4</span></span><span title="16 silver badges"><span class="badge2"></span><span class="badgecount">16</span></span><span title="28 bronze badges"><span class="badge3"></span><span class="badgecount">28</span></span>
        </div>
    </div>

    </td>
    </tr>
    </tbody></table>
</div>
</td>
        </tr>


<tr>
<td class="votecell"></td>
<td>
    <div id="comments-1141979" class="comments dno">
        <table>
        <tbody>
        
            <tr><td></td><td></td></tr>
        
        </tbody>
    
        </table>
    </div>
    
</td>
</tr>            </tbody></table>    
</div>


<div id="answers">

    <a name="tab-top"></a>
    <div id="answers-header">
        <div class="subheader answers-subheader">
            <h2>
                    5 Answers
            </h2>
            <div id="tabs">
                <a href="http://stackoverflow.com/questions/1141979/javascript-for-add-to-home-screen-on-iphone?answertab=active#tab-top" title="Answers with the latest activity first">active</a>
<a href="http://stackoverflow.com/questions/1141979/javascript-for-add-to-home-screen-on-iphone?answertab=oldest#tab-top" title="Answers in the order they were provided">oldest</a>
<a class="youarehere" href="http://stackoverflow.com/questions/1141979/javascript-for-add-to-home-screen-on-iphone?answertab=votes#tab-top" title="Answers with the highest score first">votes</a>

            </div>
        </div>    
    </div>    




  
<a name="1142008"></a>
<div id="answer-1142008" class="answer accepted-answer" data-answerid="1142008">
    <table>
        <tbody><tr>
            <td class="votecell">
                

<div class="vote">
    <input value="1142008" type="hidden">
    <a class="vote-up-off" title="This answer is useful">up vote</a>
    <span class="vote-count-post ">23</span>
    <a class="vote-down-off" title="This answer is not useful">down vote</a>
    

            <span class="vote-accepted-on load-accepted-answer-date" title="loading when this answer was accepted...">accepted</span>
</div>

            </td>
            


<td class="answercell">
    <div class="post-text"><p>The only way to add any book marks in 
MobileSafari (including ones on the home screen) is with the builtin UI,
 and that Apples does not provide anyway to do this from scripts within a
 page. In fact, I am pretty sure there is no mechanism for doing this on
 the desktop version of Safari either.</p>
</div>
    <table class="fw">
    <tbody><tr>
    <td class="vt">










<div class="post-menu"><a href="http://stackoverflow.com/a/1142008" title="short permalink to this answer" class="short-link" id="link-post-1142008">share</a><span class="lsep">|</span><a href="http://stackoverflow.com/posts/1142008/edit" class="suggest-edit-post" title="">improve this answer</a></div>                    </td>
            


    <td class="post-signature" align="right">   
       

    

    <div class="user-info user-hover">
        <div class="user-action-time">
                                        answered <span title="2009-07-17 08:24:51Z" class="relativetime">Jul 17 '09 at 8:24</span>
        </div>
        <div class="user-gravatar32">
            <a href="http://stackoverflow.com/users/30506/louis-gerbarg"><div class=""><img src="mobile_bookmark_files/c5c13a74e56f720200e100130c68ac53.jpg" alt="" height="32" width="32"></div></a>
        </div>
        <div class="user-details">
            <a href="http://stackoverflow.com/users/30506/louis-gerbarg">Louis Gerbarg</a><br>
            <span class="reputation-score" title="reputation score 29004" dir="ltr">29k</span><span title="4 gold badges"><span class="badge1"></span><span class="badgecount">4</span></span><span title="55 silver badges"><span class="badge2"></span><span class="badgecount">55</span></span><span title="73 bronze badges"><span class="badge3"></span><span class="badgecount">73</span></span>
        </div>
    </div>

    </td>
    </tr>
    </tbody></table>
</td>
        </tr>



<tr>
<td class="votecell"></td>
<td>
    <div id="comments-1142008" class="comments">
        <table>
        <tbody>
                    
    <tr id="comment-961226" class="comment">
        <td class="comment-actions"><table><tbody><tr>
<td class="comment-score">
<span title="number of 'useful comment' votes received" class="cool">3</span>
</td>
<td>
&nbsp;
</td></tr>
</tbody></table></td>
        <td class="comment-text"><div><span class="comment-copy">Thanks, I was afraid not. I decided to check <code>window.navigator.standalone</code> and urge them to add it if it in running in Mobile Safari.</span> –&nbsp;<a href="http://stackoverflow.com/users/126329/kerrick" title="1779 reputation" class="comment-user owner">Kerrick</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment961226_1142008"><span title="2009-07-17 13:39:26Z" class="relativetime-clean">Jul 17 '09 at 13:39</span></a></span></div></td>
    </tr>
            
    <tr id="comment-3865632" class="comment">
        <td class="comment-actions"><table><tbody><tr>
<td class="comment-score">
<span title="number of 'useful comment' votes received" class="cool">1</span>
</td>
<td>
&nbsp;
</td></tr>
</tbody></table></td>
        <td class="comment-text"><div><span class="comment-copy">@David - I disagree, it's not an obvious feature to many.</span> –&nbsp;<a href="http://stackoverflow.com/users/107277/matt-huggins" title="11516 reputation" class="comment-user">Matt Huggins</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment3865632_1142008"><span title="2010-09-08 21:34:31Z" class="relativetime-clean">Sep 8 '10 at 21:34</span></a></span></div></td>
    </tr>
            
    <tr id="comment-4000030" class="comment">
        <td class="comment-actions"><table><tbody><tr>
<td class="comment-score">
<span title="number of 'useful comment' votes received" class="warm">10</span>
</td>
<td>
&nbsp;
</td></tr>
</tbody></table></td>
        <td class="comment-text"><div><span class="comment-copy">@David 
Not for web-apps. It's not many users that know they can bookmark to the
 home screen. IMHO it would be nice with a link/button that fires the 
dialog with a helpful message.</span> –&nbsp;<a href="http://stackoverflow.com/users/44643/gregers" title="2352 reputation" class="comment-user">gregers</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment4000030_1142008"><span title="2010-09-23 11:27:08Z" class="relativetime-clean">Sep 23 '10 at 11:27</span></a></span></div></td>
    </tr>
            
    <tr id="comment-5356020" class="comment">
        <td class="comment-actions"><table><tbody><tr>
<td class="comment-score">
<span title="number of 'useful comment' votes received" class="warm">10</span>
</td>
<td>
&nbsp;
</td></tr>
</tbody></table></td>
        <td class="comment-text"><div><span class="comment-copy">@David 
It isn't begging. Web apps on iOS can run as native apps, full screen if
 they are added to home screen. Even offline mode is possible so it 
would be cool if we can use javascript to add it to home screen (With 
proper dialog of corse).</span> –&nbsp;<a href="http://stackoverflow.com/users/559110/the-nakos" title="607 reputation" class="comment-user">the_nakos</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment5356020_1142008"><span title="2011-01-28 11:44:33Z" class="relativetime-clean">Jan 28 '11 at 11:44</span></a></span></div></td>
    </tr>

        </tbody>
    
        </table>
    </div>
    
</td>
</tr>
    </tbody></table>
</div>
            <div class="everyonelovesstackoverflow adzerk-vote" id="adzerk3"><a href="http://engine.adzerk.net/r?e=eyJhdiI6NDE0LCJhdCI6NCwiY20iOjg0NywiY2giOjExNzgsImNyIjo1OTE2LCJkaSI6IjZiYzg2NTJjMjM3YjQxMDNiNzBjNGZlOTc3ODA2YWQyIiwiZG0iOjEsImZjIjo4ODAyLCJmbCI6MjQ0NCwia3ciOiJqYXZhc2NyaXB0LGlwaG9uZSxtb2JpbGUtc2FmYXJpLGJvb2ttYXJrcyxob21lLXNjcmVlbiIsIm53IjoyMiwicmYiOiJodHRwOi8vd3d3Lmdvb2dsZS5jb20vdXJsP3NhPXQiLCJydiI6MCwicHIiOjE1NjgsInN0Ijo4Mjc3LCJ6biI6NDQsInVyIjoiaHR0cDovL2NhcmVlcnMuc3RhY2tvdmVyZmxvdy5jb20vIn0&amp;s=-RG11NEzCNMI7Hyv67Cj7KrFKhw" rel="nofollow" target="_blank" title=""><img src="mobile_bookmark_files/d31c8d7d3cf640a6b29d9ad71788dd4a.png" title="" alt="" border="0" height="90" width="728"></a><img src="mobile_bookmark_files/i_003.gif" border="0" height="0px" width="0px"><div class="adzerk-vote-controls" style="display: none;"><div class="adzerk-vote-option adzerk-vote-up"><div class="adzerk-vote-icon"></div></div><div class="adzerk-vote-option adzerk-vote-down"><div class="adzerk-vote-icon"></div></div></div><div class="adzerk-vote-survey" style="display:none;"><form><span>No problem. We won't show you that ad again. Why didn't you like it?</span><ul><li><label><input value="12" name="downvoteReason" type="radio">Uninteresting</label></li><li><label><input value="13" name="downvoteReason" type="radio">Misleading</label></li><li><label><input value="14" name="downvoteReason" type="radio">Offensive</label></li><li><label><input value="15" name="downvoteReason" type="radio">Repetitive</label></li></ul><a href="#" class="adzerk-vote-cancel">Oops! I didn't mean to do this.</a></form></div></div>



  
<a name="4976474"></a>
<div id="answer-4976474" class="answer" data-answerid="4976474">
    <table>
        <tbody><tr>
            <td class="votecell">
                

<div class="vote">
    <input value="4976474" type="hidden">
    <a class="vote-up-off" title="This answer is useful">up vote</a>
    <span class="vote-count-post ">35</span>
    <a class="vote-down-off" title="This answer is not useful">down vote</a>
    

</div>

            </td>
            


<td class="answercell">
    <div class="post-text"><p>I had the same issue and there is 
(rightly, probably) no way to add it programatically, but came across 
this great little snippet which prompts the user to do it and even 
points to the right spot.  Works a treat.</p>

<p><a href="http://code.google.com/p/mobile-bookmark-bubble/">http://code.google.com/p/mobile-bookmark-bubble/</a></p>
</div>
    <table class="fw">
    <tbody><tr>
    <td class="vt">










<div class="post-menu"><a href="http://stackoverflow.com/a/4976474" title="short permalink to this answer" class="short-link" id="link-post-4976474">share</a><span class="lsep">|</span><a href="http://stackoverflow.com/posts/4976474/edit" class="suggest-edit-post" title="">improve this answer</a></div>                    </td>
            


    <td class="post-signature" align="right">   
       

    

    <div class="user-info ">
        <div class="user-action-time">
                                        answered <span title="2011-02-12 05:11:45Z" class="relativetime">Feb 12 '11 at 5:11</span>
        </div>
        <div class="user-gravatar32">
            <a href="http://stackoverflow.com/users/613953/craig"><div class=""><img src="mobile_bookmark_files/bce25d52cd6b3355ca748fb5c41c0515.png" alt="" height="32" width="32"></div></a>
        </div>
        <div class="user-details">
            <a href="http://stackoverflow.com/users/613953/craig">Craig</a><br>
            <span class="reputation-score" title="reputation score" dir="ltr">351</span><span title="3 silver badges"><span class="badge2"></span><span class="badgecount">3</span></span><span title="2 bronze badges"><span class="badge3"></span><span class="badgecount">2</span></span>
        </div>
    </div>

    </td>
    </tr>
    </tbody></table>
</td>
        </tr>



<tr>
<td class="votecell"></td>
<td>
    <div id="comments-4976474" class="comments">
        <table>
        <tbody>
                    
    <tr id="comment-16695625" class="comment">
        <td class="comment-actions"><table><tbody><tr>
<td class="comment-score">
<span title="number of 'useful comment' votes received" class="cool">3</span>
</td>
<td>
&nbsp;
</td></tr>
</tbody></table></td>
        <td class="comment-text"><div><span class="comment-copy">This is now on github - <a href="https://github.com/h5bp/mobile-boilerplate/wiki/Mobile-Bookmark-Bubble" rel="nofollow">github.com/h5bp/mobile-boilerplate/wiki/Mobile-Bookmark-Bubble</a></span> –&nbsp;<a href="http://stackoverflow.com/users/717321/matt" title="68 reputation" class="comment-user">matt</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment16695625_4976474"><span title="2012-09-14 07:59:55Z" class="relativetime-clean">Sep 14 '12 at 7:59</span></a></span></div></td>
    </tr>
            
    <tr id="comment-17139074" class="comment">
        <td class="comment-actions"><table><tbody><tr>
<td class="comment-score">
<span title="number of 'useful comment' votes received" class="warm">5</span>
</td>
<td>
&nbsp;
</td></tr>
</tbody></table></td>
        <td class="comment-text"><div><span class="comment-copy"><a href="http://cubiq.org/add-to-home-screen" rel="nofollow">cubiq.org/add-to-home-screen</a> may be a better link that demos it and has good info on it.</span> –&nbsp;<a href="http://stackoverflow.com/users/122364/luke-stanley" title="446 reputation" class="comment-user">Luke Stanley</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment17139074_4976474"><span title="2012-10-02 18:13:40Z" class="relativetime-clean">Oct 2 '12 at 18:13</span></a></span></div></td>
    </tr>
            
    <tr id="comment-23137643" class="comment">
        <td></td>
        <td class="comment-text"><div><span class="comment-copy">Sadly, this is the best solution currently available.</span> –&nbsp;<a href="http://stackoverflow.com/users/414385/hitautodestruct" title="1446 reputation" class="comment-user">hitautodestruct</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment23137643_4976474"><span title="2013-04-24 06:42:57Z" class="relativetime-clean">Apr 24 at 6:42</span></a></span></div></td>
    </tr>

        </tbody>
    
        </table>
    </div>
    
</td>
</tr>
    </tbody></table>
</div>

  
<a name="8679136"></a>
<div id="answer-8679136" class="answer" data-answerid="8679136">
    <table>
        <tbody><tr>
            <td class="votecell">
                

<div class="vote">
    <input value="8679136" type="hidden">
    <a class="vote-up-off" title="This answer is useful">up vote</a>
    <span class="vote-count-post ">17</span>
    <a class="vote-down-off" title="This answer is not useful">down vote</a>
    

</div>

            </td>
            


<td class="answercell">
    <div class="post-text"><p>Another script that triggers an 'Add To Home Screen' popup: <a href="http://cubiq.org/add-to-home-screen">http://cubiq.org/add-to-home-screen</a></p>
</div>
    <table class="fw">
    <tbody><tr>
    <td class="vt">










<div class="post-menu"><a href="http://stackoverflow.com/a/8679136" title="short permalink to this answer" class="short-link" id="link-post-8679136">share</a><span class="lsep">|</span><a href="http://stackoverflow.com/posts/8679136/edit" class="suggest-edit-post" title="">improve this answer</a></div>                    </td>
            


    <td class="post-signature" align="right">   
       

    

    <div class="user-info ">
        <div class="user-action-time">
                                        answered <span title="2011-12-30 11:47:22Z" class="relativetime">Dec 30 '11 at 11:47</span>
        </div>
        <div class="user-gravatar32">
            <a href="http://stackoverflow.com/users/23805/bob"><div class=""><img src="mobile_bookmark_files/68de8ce64520b4d821d253cca985ef78.png" alt="" height="32" width="32"></div></a>
        </div>
        <div class="user-details">
            <a href="http://stackoverflow.com/users/23805/bob">bob</a><br>
            <span class="reputation-score" title="reputation score" dir="ltr">2,668</span><span title="1 gold badge"><span class="badge1"></span><span class="badgecount">1</span></span><span title="13 silver badges"><span class="badge2"></span><span class="badgecount">13</span></span><span title="16 bronze badges"><span class="badge3"></span><span class="badgecount">16</span></span>
        </div>
    </div>

    </td>
    </tr>
    </tbody></table>
</td>
        </tr>



<tr>
<td class="votecell"></td>
<td>
    <div id="comments-8679136" class="comments">
        <table>
        <tbody>
                    
    <tr id="comment-12783069" class="comment">
        <td></td>
        <td class="comment-text"><div><span class="comment-copy">looks awesome!!!</span> –&nbsp;<a href="http://stackoverflow.com/users/403717/nurne" title="406 reputation" class="comment-user">nurne</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment12783069_8679136"><span title="2012-04-03 20:06:11Z" class="relativetime-clean">Apr 3 '12 at 20:06</span></a></span></div></td>
    </tr>
            
    <tr id="comment-16952623" class="comment">
        <td></td>
        <td class="comment-text"><div><span class="comment-copy">This looks the best, more programmable behaviour like returningVisitor.</span> –&nbsp;<a href="http://stackoverflow.com/users/694629/bendihossan" title="366 reputation" class="comment-user">Bendihossan</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment16952623_8679136"><span title="2012-09-25 10:58:19Z" class="relativetime-clean">Sep 25 '12 at 10:58</span></a></span></div></td>
    </tr>
            
    <tr id="comment-24331262" class="comment">
        <td></td>
        <td class="comment-text"><div><span class="comment-copy">Hands down, this is the best. It took me just a few minutes to implement, and another 5 to customize the message. Way2Go!</span> –&nbsp;<a href="http://stackoverflow.com/users/700111/techdude" title="63 reputation" class="comment-user">techdude</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment24331262_8679136"><span title="2013-06-01 00:14:53Z" class="relativetime-clean">Jun 1 at 0:14</span></a></span></div></td>
    </tr>

        </tbody>
    
        </table>
    </div>
    
</td>
</tr>
    </tbody></table>
</div>

  
<a name="3830830"></a>
<div id="answer-3830830" class="answer" data-answerid="3830830">
    <table>
        <tbody><tr>
            <td class="votecell">
                

<div class="vote">
    <input value="3830830" type="hidden">
    <a class="vote-up-off" title="This answer is useful">up vote</a>
    <span class="vote-count-post ">6</span>
    <a class="vote-down-off" title="This answer is not useful">down vote</a>
    

</div>

            </td>
            


<td class="answercell">
    <div class="post-text"><p>There is an open source Javascript library that offers something related :
<a href="http://code.google.com/p/mobile-bookmark-bubble/" rel="nofollow">mobile-bookmark-bubble</a></p>

<blockquote>
  <p>The Mobile Bookmark Bubble is a JavaScript library that adds a 
promo bubble to the bottom of your mobile web application, inviting 
users to bookmark the app to their device's home screen. The library 
uses HTML5 local storage to track whether the promo has been displayed 
already, to avoid constantly nagging users.</p>
  
  <p>The current implementation of this library specifically targets Mobile Safari, the web browser used on iPhone and iPad devices.</p>
</blockquote>
</div>
    <table class="fw">
    <tbody><tr>
    <td class="vt">










<div class="post-menu"><a href="http://stackoverflow.com/a/3830830" title="short permalink to this answer" class="short-link" id="link-post-3830830">share</a><span class="lsep">|</span><a href="http://stackoverflow.com/posts/3830830/edit" class="suggest-edit-post" title="">improve this answer</a></div>                    </td>
            


    <td class="post-signature" align="right">   
       

    

    <div class="user-info ">
        <div class="user-action-time">
                                        answered <span title="2010-09-30 13:14:15Z" class="relativetime">Sep 30 '10 at 13:14</span>
        </div>
        <div class="user-gravatar32">
            <a href="http://stackoverflow.com/users/462835/philippe-laval"><div class=""><img src="mobile_bookmark_files/702929883b884470793a0b7beb21a201.jpg" alt="" height="32" width="32"></div></a>
        </div>
        <div class="user-details">
            <a href="http://stackoverflow.com/users/462835/philippe-laval">Philippe Laval</a><br>
            <span class="reputation-score" title="reputation score" dir="ltr">61</span><span title="1 silver badge"><span class="badge2"></span><span class="badgecount">1</span></span><span title="2 bronze badges"><span class="badge3"></span><span class="badgecount">2</span></span>
        </div>
    </div>

    </td>
    </tr>
    </tbody></table>
</td>
        </tr>



<tr>
<td class="votecell"></td>
<td>
    <div id="comments-3830830" class="comments">
        <table>
        <tbody>
                    
    <tr id="comment-10871155" class="comment">
        <td class="comment-actions"><table><tbody><tr>
<td class="comment-score">
<span title="number of 'useful comment' votes received" class="cool">1</span>
</td>
<td>
&nbsp;
</td></tr>
</tbody></table></td>
        <td class="comment-text"><div><span class="comment-copy">Is there anything similar for Android (or, ugh, dare I say, Blackberry)?</span> –&nbsp;<a href="http://stackoverflow.com/users/786301/snowboardbruin" title="1238 reputation" class="comment-user">SnowboardBruin</a> <span class="comment-date" dir="ltr"><a class="comment-link" href="#comment10871155_3830830"><span title="2012-01-04 16:34:23Z" class="relativetime-clean">Jan 4 '12 at 16:34</span></a></span></div></td>
    </tr>

        </tbody>
    
        </table>
    </div>
    
</td>
</tr>
    </tbody></table>
</div>

  
<a name="13816754"></a>
<div id="answer-13816754" class="answer" data-answerid="13816754">
    <table>
        <tbody><tr>
            <td class="votecell">
                

<div class="vote">
    <input value="13816754" type="hidden">
    <a class="vote-up-off" title="This answer is useful">up vote</a>
    <span class="vote-count-post ">0</span>
    <a class="vote-down-off" title="This answer is not useful">down vote</a>
    

</div>

            </td>
            


<td class="answercell">
    <div class="post-text"><p>This is also another good Home Screen 
script that support iphone/ipad, Mobile Safari, Android, Blackberry 
touch smartphones and Playbook .</p>

<p><a href="https://github.com/h5bp/mobile-boilerplate/wiki/Mobile-Bookmark-Bubble" rel="nofollow">https://github.com/h5bp/mobile-boilerplate/wiki/Mobile-Bookmark-Bubble</a></p>
</div>
    <table class="fw">
    <tbody><tr>
    <td class="vt">










<div class="post-menu"><a href="http://stackoverflow.com/a/13816754" title="short permalink to this answer" class="short-link" id="link-post-13816754">share</a><span class="lsep">|</span><a href="http://stackoverflow.com/posts/13816754/edit" class="suggest-edit-post" title="">improve this answer</a></div>                    </td>
            


    <td class="post-signature" align="right">   
       

    

    <div class="user-info ">
        <div class="user-action-time">
                                        answered <span title="2012-12-11 08:57:51Z" class="relativetime">Dec 11 '12 at 8:57</span>
        </div>
        <div class="user-gravatar32">
            <a href="http://stackoverflow.com/users/795446/miuranga"><div class="gravatar-wrapper-32"><img src="mobile_bookmark_files/tF8L4.jpg" alt=""></div></a>
        </div>
        <div class="user-details">
            <a href="http://stackoverflow.com/users/795446/miuranga">miuranga</a><br>
            <span class="reputation-score" title="reputation score" dir="ltr">509</span><span title="7 silver badges"><span class="badge2"></span><span class="badgecount">7</span></span><span title="21 bronze badges"><span class="badge3"></span><span class="badgecount">21</span></span>
        </div>
    </div>

    </td>
    </tr>
    </tbody></table>
</td>
        </tr>



<tr>
<td class="votecell"></td>
<td>
    <div id="comments-13816754" class="comments dno">
        <table>
        <tbody>
        
            <tr><td></td><td></td></tr>
        
        </tbody>
    
        </table>
    </div>
    
</td>
</tr>
    </tbody></table>
</div>
    <div class="question-status">
        <h2><strong>protected</strong> by <a href="http://stackoverflow.com/users/13531/michael-myers">Michael Myers</a><span class="mod-flair" title="moderator">♦</span> <span title="2011-08-08 16:39:42Z" class="relativetime">Aug 8 '11 at 16:39</span></h2>
        <p>This question is protected to prevent "thanks!", "me too!", or spam answers by new users. 
To answer it, you must have earned at least 10 <a href="http://stackoverflow.com/help/whats-reputation">reputation</a> on this site.</p>
    </div>



        <h2 class="bottom-notice">
                Not the answer you're looking for? 
            Browse other questions tagged <a href="http://stackoverflow.com/questions/tagged/javascript" class="post-tag" title="show questions tagged 'javascript'" rel="tag">javascript</a> <a href="http://stackoverflow.com/questions/tagged/iphone" class="post-tag" title="show questions tagged 'iphone'" rel="tag">iphone</a> <a href="http://stackoverflow.com/questions/tagged/mobile-safari" class="post-tag" title="show questions tagged 'mobile-safari'" rel="tag">mobile-safari</a> <a href="http://stackoverflow.com/questions/tagged/bookmarks" class="post-tag" title="show questions tagged 'bookmarks'" rel="tag">bookmarks</a> <a href="http://stackoverflow.com/questions/tagged/home-screen" class="post-tag" title="show questions tagged 'home-screen'" rel="tag">home-screen</a> 
                or <a href="http://stackoverflow.com/questions/ask">ask your own question</a>.
        </h2>
</div>
</div>
<div id="sidebar" class="show-votes">
        <div class="module question-stats">
        <p class="label-key">tagged</p>
        <div class="tagged"><a href="http://stackoverflow.com/questions/tagged/javascript" class="post-tag" title="show questions tagged 'javascript'" rel="tag">javascript</a>&nbsp;<span class="item-multiplier"><span class="item-multiplier-x">×</span>&nbsp;<span class="item-multiplier-count">398031</span></span><br>
<a href="http://stackoverflow.com/questions/tagged/iphone" class="post-tag" title="show questions tagged 'iphone'" rel="tag">iphone</a>&nbsp;<span class="item-multiplier"><span class="item-multiplier-x">×</span>&nbsp;<span class="item-multiplier-count">175486</span></span><br>
<a href="http://stackoverflow.com/questions/tagged/mobile-safari" class="post-tag" title="show questions tagged 'mobile-safari'" rel="tag">mobile-safari</a>&nbsp;<span class="item-multiplier"><span class="item-multiplier-x">×</span>&nbsp;<span class="item-multiplier-count">2022</span></span><br>
<a href="http://stackoverflow.com/questions/tagged/bookmarks" class="post-tag" title="show questions tagged 'bookmarks'" rel="tag">bookmarks</a>&nbsp;<span class="item-multiplier"><span class="item-multiplier-x">×</span>&nbsp;<span class="item-multiplier-count">505</span></span><br>
<a href="http://stackoverflow.com/questions/tagged/home-screen" class="post-tag" title="show questions tagged 'home-screen'" rel="tag">home-screen</a>&nbsp;<span class="item-multiplier"><span class="item-multiplier-x">×</span>&nbsp;<span class="item-multiplier-count">26</span></span><br>
</div>
        <table id="qinfo">
            <tbody><tr>
                <td><p class="label-key">asked</p></td>
                <td style="padding-left:10px"><p class="label-key" title="2009-07-17 08:13:49Z"><b>3 years ago</b></p></td>
            </tr>
            <tr>
                <td><p class="label-key">viewed</p></td>

                <td style="padding-left:10px">
                    <p class="label-key">
                        <b>53941 times</b>
                    </p>
                </td>
            </tr>
            <tr>
                <td><p class="label-key">active</p></td>
                <td style="padding-left:10px"><p class="label-key"><b><a href="http://stackoverflow.com/questions/1141979/javascript-for-add-to-home-screen-on-iphone?lastactivity" class="lastactivity-link" title="2012-12-11 08:57:51Z">6 months ago</a></b></p></td>
            </tr>
        </tbody></table>
    </div>
    
<div class="module community-bulletin" data-tracker="cb=1">
    <h4>Community Bulletin</h4>
    <div class="related">
        <div class="spacer">
            <div class="bulletin-item-type"><a href="http://blog.stackoverflow.com/?cb=1" class="event-date" target="_blank">blog</a></div>
            <div class="bulletin-item-content">
                <a href="http://blog.stackoverflow.com/2013/06/the-war-of-the-closes/?cb=1" class="question-hyperlink" target="_blank">The War of the Closes</a>
            </div>
            <br class="cbt">
        </div>
    </div>
</div>    
                    <script type="text/javascript">
                    var scriptSrc = "http://engine.adzerk.net/z/8277/adzerk1_2_4_43,adzerk2_2_17_45,adzerk3_2_4_44?keywords=javascript,iphone,mobile-safari,bookmarks,home-screen";
                    if (document.referrer) {
                        if (/\?/.test(scriptSrc))
                            scriptSrc += "&";
                        else
                            scriptSrc += "?";
                        scriptSrc += "xReferrer=" + document.referrer;
                    }
                    StackExchange.ready(function() {
                        var z = document.createElement("script");
                        z.type = "text/javascript";
                        z.async = "true";
                        z.src = scriptSrc;
                        var s = document.getElementsByTagName("script")[0];
                        s.parentNode.insertBefore(z, s);
                    });
                </script>
            <div class="everyonelovesstackoverflow adzerk-vote" id="adzerk2"><a href="http://engine.adzerk.net/r?e=eyJhdiI6NDE0LCJhdCI6MTcsImNtIjo4NDcsImNoIjoxMTc4LCJjciI6OTMzNCwiZGkiOiI1MWU3MDc2MzY1NDQ0YTg2YWQ2OGM5YjAxZjI3OTY4MyIsImRtIjoxLCJmYyI6MTQ4NDQsImZsIjoyNDQ0LCJrdyI6ImphdmFzY3JpcHQsaXBob25lLG1vYmlsZS1zYWZhcmksYm9va21hcmtzLGhvbWUtc2NyZWVuIiwibnciOjIyLCJyZiI6Imh0dHA6Ly93d3cuZ29vZ2xlLmNvbS91cmw_c2E9dCIsInJ2IjowLCJwciI6MTU2OCwic3QiOjgyNzcsInpuIjo0NSwidXIiOiJodHRwOi8vY2FyZWVycy5zdGFja292ZXJmbG93LmNvbS8ifQ&amp;s=TOs1cRDmJXTkeXeYdVfZFTPTbrg" rel="nofollow" target="_blank" title=""><img src="mobile_bookmark_files/db5df4870e4e4b6cbf42727fd434701a.jpg" title="" alt="" border="0" height="250" width="220"></a><img src="mobile_bookmark_files/i.gif" border="0" height="0px" width="0px"><div class="adzerk-vote-controls" style="display: none;"><div class="adzerk-vote-option adzerk-vote-up"><div class="adzerk-vote-icon"></div></div><div class="adzerk-vote-option adzerk-vote-down"><div class="adzerk-vote-icon"></div></div></div><div class="adzerk-vote-survey" style="display:none;"><form><span>No problem. We won't show you that ad again. Why didn't you like it?</span><ul><li><label><input value="12" name="downvoteReason" type="radio">Uninteresting</label></li><li><label><input value="13" name="downvoteReason" type="radio">Misleading</label></li><li><label><input value="14" name="downvoteReason" type="radio">Offensive</label></li><li><label><input value="15" name="downvoteReason" type="radio">Repetitive</label></li></ul><a href="#" class="adzerk-vote-cancel">Oops! I didn't mean to do this.</a></form></div></div>
    <div id="hireme"> <a href="http://careers.stackoverflow.com/jobs?a=12" class="top" target="_blank"></a> <ul class="jobs"> <li> <a href="http://careers.stackoverflow.com/jobs/36625/ios-developer-athlete-com?a=HgSRZbG" target="_blank" title="iOS Developer at Athlete.com. Click to learn more."> iOS Developer<br> <span class="company">Athlete.com</span> <span class="location">Salt Lake City, UT</span> </a> </li> <li> <a href="http://careers.stackoverflow.com/jobs/35883/machine-learning-nlp-developer-c-plus-plus-contentwatch?a=GowYaXK" target="_blank" title="Machine Learning/NLP Developer (C++) at ContentWatch. Click to learn more."> Machine Learning/NLP Developer (C++)<br> <span class="company">ContentWatch</span> <span class="location">Salt Lake City, UT</span> </a> </li> <li> <a href="http://careers.stackoverflow.com/jobs/35924/senior-net-software-engineer-ebay?a=Grxb2i4" target="_blank" title="Senior .Net Software Engineer at eBay. Click to learn more."> Senior .Net Software Engineer<br> <span class="company">eBay</span> <span class="location">Draper, UT</span> </a> </li> <li class="city"><a href="http://careers.stackoverflow.com/jobs/location/salt%20lake%20city%2c%20ut%2c%20united%20states?a=vYY">More jobs near Salt Lake City...</a></li> </ul> <img alt="" class="impression" src="mobile_bookmark_files/HgSRZbG-GowYaXK-Grxb2i4-12-vYY.gif" style="display:none"></div>



    
  
    
    <div class="module sidebar-linked">
        <h4 id="h-linked">Linked</h4>
        <div class="linked" data-tracker="lq=1">
            <div class="spacer">
<a href="http://stackoverflow.com/q/6162070?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">8</div>
</a>
<a href="http://stackoverflow.com/questions/6162070/jquery-mobile-add-the-home-screen-options?lq=1" class="question-hyperlink">JQuery Mobile Add the Home Screen Options?</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/618431?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">6</div>
</a>
<a href="http://stackoverflow.com/questions/618431/iphone-bookmark-link-add-to-home-screen?lq=1" class="question-hyperlink">iPhone bookmark link add to home screen?</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/7498085?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">1</div>
</a>
<a href="http://stackoverflow.com/questions/7498085/ios-and-android-create-a-bookmark-shortcut-in-homescreen-by-javascript?lq=1" class="question-hyperlink">iOs And Android: create a bookmark -shortcut- in homescreen by javascript</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/10836925?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">4</div>
</a>
<a href="http://stackoverflow.com/questions/10836925/iphone-sdk-add-a-add-to-home-screen-button-in-a-uiwebview?lq=1" class="question-hyperlink">iPhone SDK - Add a “Add to Home Screen” button in a UIWebView</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/7009000?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">0</div>
</a>
<a href="http://stackoverflow.com/questions/7009000/iphone-add-home-screen-icon-automatically?lq=1" class="question-hyperlink">iphone, add home screen icon automatically</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/6858964?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">3</div>
</a>
<a href="http://stackoverflow.com/questions/6858964/ios-add-home-screen-shortcut-programmatically?lq=1" class="question-hyperlink">iOS add home screen shortcut programmatically</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/5673536?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">0</div>
</a>
<a href="http://stackoverflow.com/questions/5673536/iphone-webclip-browser-app-suggestions?lq=1" class="question-hyperlink">iPhone webClip browser app suggestions</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/9578647?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">1</div>
</a>
<a href="http://stackoverflow.com/questions/9578647/check-ios-homepage-install-html5?lq=1" class="question-hyperlink">Check iOS Homepage Install - HTML5</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/12875960?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">2</div>
</a>
<a href="http://stackoverflow.com/questions/12875960/implement-add-to-home-screen-in-mobile-website-is-it-possible?lq=1" class="question-hyperlink">Implement “Add to Home Screen” in mobile website. Is it possible?</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/7022359?lq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">0</div>
</a>
<a href="http://stackoverflow.com/questions/7022359/save-webpage-link-on-iphones-desktop-programaticaly?lq=1" class="question-hyperlink">save webpage link on iphone's desktop programaticaly</a>
</div>
<div class="spacer more">
<a href="http://stackoverflow.com/questions/linked/1141979?lq=1">see more linked questions…</a></div>

        </div>
    </div>
    <div class="module sidebar-related">
        <h4 id="h-related">Related</h4>
        <div class="related" data-tracker="rq=1">
            <div class="spacer">
<a href="http://stackoverflow.com/q/618431?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">6</div>
</a>
<a href="http://stackoverflow.com/questions/618431/iphone-bookmark-link-add-to-home-screen?rq=1" class="question-hyperlink">iPhone bookmark link add to home screen?</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/1255605?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">0</div>
</a>
<a href="http://stackoverflow.com/questions/1255605/iphone-bookmarks-and-session-variables-user-has-to-log-in-twice?rq=1" class="question-hyperlink">iPhone Bookmarks and Session variables (User has to log in twice)</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/7015962?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">4</div>
</a>
<a href="http://stackoverflow.com/questions/7015962/detect-an-app-on-home-screen-of-iphone?rq=1" class="question-hyperlink">Detect an app on home screen of iphone</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/7451864?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">0</div>
</a>
<a href="http://stackoverflow.com/questions/7451864/ios-home-screen-bookmark-avoid-getting-pulled-out-to-mobilesafari?rq=1" class="question-hyperlink">iOS Home Screen Bookmark - avoid getting pulled out to mobileSafari?</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/8355642?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">0</div>
</a>
<a href="http://stackoverflow.com/questions/8355642/bookmark-certain-page-on-site-regardless-of-what-page-your-on-for-iphone-when-ad?rq=1" class="question-hyperlink">Bookmark certain page on site regardless of what page your on for iPhone when adding to home screen?</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/9394736?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">1</div>
</a>
<a href="http://stackoverflow.com/questions/9394736/smartphone-home-screen-bookmark-button?rq=1" class="question-hyperlink">Smartphone home screen bookmark button</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/10796055?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">1</div>
</a>
<a href="http://stackoverflow.com/questions/10796055/can-i-set-the-default-page-for-a-web-capable-site-added-to-the-home-screen?rq=1" class="question-hyperlink">Can i set the default page for a web-capable site added to the home screen?</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/10836925?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">4</div>
</a>
<a href="http://stackoverflow.com/questions/10836925/iphone-sdk-add-a-add-to-home-screen-button-in-a-uiwebview?rq=1" class="question-hyperlink">iPhone SDK - Add a “Add to Home Screen” button in a UIWebView</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/11616181?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes answered-accepted default">4</div>
</a>
<a href="http://stackoverflow.com/questions/11616181/iphone-web-app-from-home-screen-always-reloads-switching-between-apps?rq=1" class="question-hyperlink">iPhone web-app from home screen always reloads switching between apps</a>
</div>
<div class="spacer">
<a href="http://stackoverflow.com/q/12154191?rq=1" title="Vote score (upvotes - downvotes)">
        <div class="answer-votes default">2</div>
</a>
<a href="http://stackoverflow.com/questions/12154191/adding-bookmark-to-mobile-browser-using-javascript?rq=1" class="question-hyperlink">adding bookmark to mobile browser using javascript</a>
</div>

        </div>
    </div>
</div>

<div id="feed-link">
    <div id="feed-link-text"><a href="http://stackoverflow.com/feeds/question/1141979" title="feed of this question and its answers"><span class="feed-icon"></span>question feed</a></div>
</div>
<script type="text/javascript">
StackExchange.ready(function(){$.get('/posts/1141979/ivc/de54');});
</script>
<noscript>
    <div><img src="/posts/1141979/ivc/de54" class="dno" alt="" width="0" height="0"></div>
</noscript><div style="display:none" id="prettify-lang">default</div></div>


        </div>
    </div>
    <div id="footer" class="categories">
        <div class="footerwrap">
            <div id="footer-menu">
                <div class="top-footer-links">
                        <a href="http://stackoverflow.com/about">about</a>
                    <a href="http://stackoverflow.com/help">help</a>
                        <a href="http://stackoverflow.com/help/badges">badges</a>
                    <a href="http://blog.stackexchange.com/?blb=1">blog</a>
                        <a href="http://chat.stackoverflow.com/">chat</a>
                    <a href="http://data.stackexchange.com/">data</a>
                    <a href="http://stackexchange.com/legal">legal</a>
                    <a href="http://stackexchange.com/legal/privacy-policy">privacy policy</a>
                    <a href="http://stackexchange.com/about/hiring">jobs</a>
                    <a href="http://engine.adzerk.net/redirect/0/2776/2751/0/4de3c60f719c4dfcb1a57531c7050090/0">advertising info</a>

                    <a onclick='StackExchange.switchMobile("on", "/questions/1141979/javascript-for-add-to-home-screen-on-iphone")'>mobile</a>
                    <b><a href="http://stackoverflow.com/contact">contact us</a></b>
                        <b><a href="http://meta.stackoverflow.com/">feedback</a></b>
                </div>
                <div id="footer-sites">
                <table>
    <tbody><tr>
            <th colspan="3">
                Technology
            </th>
            <th>
                Life / Arts
            </th>
            <th>
                Culture / Recreation
            </th>
            <th>
                Science
            </th>
            <th>
                Other
            </th>
    </tr>
    <tr>
            <td>
                <ol>
                        <li><a href="http://stackoverflow.com/" title="professional and enthusiast programmers">Stack Overflow</a></li>
                        <li><a href="http://serverfault.com/" title="professional system and network administrators">Server Fault</a></li>
                        <li><a href="http://superuser.com/" title="computer enthusiasts and power users">Super User</a></li>
                        <li><a href="http://webapps.stackexchange.com/" title="power users of web applications">Web Applications</a></li>
                        <li><a href="http://askubuntu.com/" title="Ubuntu users and developers">Ask Ubuntu</a></li>
                        <li><a href="http://webmasters.stackexchange.com/" title="pro webmasters">Webmasters</a></li>
                        <li><a href="http://gamedev.stackexchange.com/" title="professional and independent game developers">Game Development</a></li>
                        <li><a href="http://tex.stackexchange.com/" title="users of TeX, LaTeX, ConTeXt, and related typesetting systems">TeX - LaTeX</a></li>
                            </ol></td><td><ol>
                        <li><a href="http://programmers.stackexchange.com/" title="professional programmers interested in conceptual questions about software development">Programmers</a></li>
                        <li><a href="http://unix.stackexchange.com/" title="users of Linux, FreeBSD and other Un*x-like operating systems.">Unix &amp; Linux</a></li>
                        <li><a href="http://apple.stackexchange.com/" title="power users of Apple hardware and software">Ask Different (Apple)</a></li>
                        <li><a href="http://wordpress.stackexchange.com/" title="WordPress developers and administrators">WordPress Answers</a></li>
                        <li><a href="http://gis.stackexchange.com/" title="cartographers, geographers and GIS professionals">Geographic Information Systems</a></li>
                        <li><a href="http://electronics.stackexchange.com/" title="electronics and electrical engineering professionals, students, and enthusiasts">Electrical Engineering</a></li>
                        <li><a href="http://android.stackexchange.com/" title="enthusiasts and power users of the Android operating system">Android Enthusiasts</a></li>
                        <li><a href="http://security.stackexchange.com/" title="IT security professionals">IT Security</a></li>
                            </ol></td><td><ol>
                        <li><a href="http://dba.stackexchange.com/" title="database professionals who wish to improve their database skills and learn from others in the community">Database Administrators</a></li>
                        <li><a href="http://drupal.stackexchange.com/" title="Drupal developers and administrators">Drupal Answers</a></li>
                        <li><a href="http://sharepoint.stackexchange.com/" title="SharePoint enthusiasts">SharePoint</a></li>
                        <li><a href="http://ux.stackexchange.com/" title="user experience researchers and experts">User Experience</a></li>
                        <li><a href="http://mathematica.stackexchange.com/" title="users of Mathematica">Mathematica</a></li>
                    
                        <li>
                            <a href="http://stackexchange.com/sites#technology" class="more">
                                more (13)
                            </a>
                        </li>
                </ol>
            </td>
            <td>
                <ol>
                        <li><a href="http://photo.stackexchange.com/" title="professional, enthusiast and amateur photographers">Photography</a></li>
                        <li><a href="http://scifi.stackexchange.com/" title="science fiction and fantasy enthusiasts">Science Fiction &amp; Fantasy</a></li>
                        <li><a href="http://cooking.stackexchange.com/" title="professional and amateur chefs">Seasoned Advice (cooking)</a></li>
                        <li><a href="http://diy.stackexchange.com/" title="contractors and serious DIYers">Home Improvement</a></li>
                    
                        <li>
                            <a href="http://stackexchange.com/sites#lifearts" class="more">
                                more (13)
                            </a>
                        </li>
                </ol>
            </td>
            <td>
                <ol>
                        <li><a href="http://english.stackexchange.com/" title="linguists, etymologists, and serious English language enthusiasts">English Language &amp; Usage</a></li>
                        <li><a href="http://skeptics.stackexchange.com/" title="scientific skepticism">Skeptics</a></li>
                        <li><a href="http://judaism.stackexchange.com/" title="those who base their lives on Jewish law and tradition and anyone interested in learning more">Mi Yodeya (Judaism)</a></li>
                        <li><a href="http://travel.stackexchange.com/" title="road warriors and seasoned travelers">Travel</a></li>
                        <li><a href="http://christianity.stackexchange.com/" title="committed Christians, experts in Christianity and those interested in learning more">Christianity</a></li>
                        <li><a href="http://gaming.stackexchange.com/" title="passionate videogamers on all platforms">Arqade (gaming)</a></li>
                        <li><a href="http://bicycles.stackexchange.com/" title="people who build and repair bicycles, people who train cycling, or commute on bicycles">Bicycles</a></li>
                        <li><a href="http://rpg.stackexchange.com/" title="gamemasters and players of tabletop, paper-and-pencil role-playing games">Role-playing Games</a></li>
                    
                        <li>
                            <a href="http://stackexchange.com/sites#culturerecreation" class="more">
                                more (21)
                            </a>
                        </li>
                </ol>
            </td>
            <td>
                <ol>
                        <li><a href="http://mathoverflow.net/" title="mathematicians">MathOverflow</a></li>
                        <li><a href="http://math.stackexchange.com/" title="people studying math at any level and professionals in related fields">Mathematics</a></li>
                        <li><a href="http://stats.stackexchange.com/" title="statisticians, data analysts, data miners and data visualization experts">Cross Validated (stats)</a></li>
                        <li><a href="http://cstheory.stackexchange.com/" title="theoretical computer scientists and researchers in related fields">Theoretical Computer Science</a></li>
                        <li><a href="http://physics.stackexchange.com/" title="active researchers, academics and students of physics">Physics</a></li>
                    
                        <li>
                            <a href="http://stackexchange.com/sites#science" class="more">
                                more (7)
                            </a>
                        </li>
                </ol>
            </td>
            <td>
                <ol>
                        <li><a href="http://stackapps.com/" title="apps, scripts, and development with the Stack Exchange API">Stack Apps</a></li>
                        <li><a href="http://meta.stackoverflow.com/" title="meta-discussion of the Stack Exchange family of Q&amp;A websites">Meta Stack Overflow</a></li>
                        <li><a href="http://area51.stackexchange.com/" title="proposing new sites in the Stack Exchange network">Area 51</a></li>
                        <li><a href="http://careers.stackoverflow.com/">Stack Overflow Careers</a></li>
                    
                </ol>
            </td>
    </tr>
</tbody></table>
                </div>
            </div>

            <div id="copyright">
                site design / logo © 2013 stack exchange inc; 
                user contributions licensed under <a href="http://creativecommons.org/licenses/by-sa/3.0/" rel="license">cc-wiki</a> 
                with <a href="http://blog.stackoverflow.com/2009/06/attribution-required/" rel="license">attribution required</a>
            </div>
            <div id="footer-flair">
                <a href="http://creativecommons.org/licenses/by-sa/3.0/" class="cc-wiki-link"></a>
            </div>
            <div id="svnrev">
                rev 2013.6.28.788
            </div>
            
        </div>
    <iframe id="global-auth-frame" style="display:none" src="mobile_bookmark_files/read.htm"></iframe></div>
    <noscript>
        <div id="noscript-warning">Stack Overflow works best with JavaScript enabled<img src="http://pixel.quantserve.com/pixel/p-c1rF4kxgLUzNc.gif" alt="" class="dno"></div>
    </noscript>
    <script type="text/javascript">var _gaq=_gaq||[];_gaq.push(['_setAccount','UA-5620270-1']);
        _gaq.push(['_setCustomVar', 1, 'tags', '|javascript|iphone|mobile-safari|bookmarks|home-screen|']); 
_gaq.push(['_trackPageview']);
    var _qevents = _qevents || [];
    (function(){
        var s=document.getElementsByTagName('script')[0];
        var ga=document.createElement('script');
        ga.type='text/javascript';
        ga.async=true;
        ga.src='http://www.google-analytics.com/ga.js';
        s.parentNode.insertBefore(ga,s);
        var sc=document.createElement('script');
        sc.type='text/javascript';
        sc.async=true;
        sc.src='http://edge.quantserve.com/quant.js'; 
        s.parentNode.insertBefore(sc,s);
    })();
    </script>
    <script type="text/javascript">
        _qevents.push({ qacct: "p-c1rF4kxgLUzNc" });
    </script>        
    

</body></html>
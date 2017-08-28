<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
      <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>

<title>Yahoo! UI Library - Tree Control</title>
<link rel="stylesheet" type="text/css" href="../../build/reset/reset.css" />
<link rel="stylesheet" type="text/css" href="../../build/base/base.css" />
<link rel="stylesheet" type="text/css" href="css/screen.css" />
<link rel="stylesheet" type="text/css" href="../src/assets/treeview.css" />

</head>
  
<body>

<div id="pageTitle">
    <h3>Tree Control</h3>
</div>

<?php include('inc-alljs.php'); ?>
<?php include('inc-rightbar.php'); ?>

  <div id="content">
    <form id="mainForm" action="javscript:;">
    <div class="newsItem">
      <h3>Default TreeView Widget</h3>
      <p>
        
      </p>

      <div id="expandcontractdiv">
        <a href="javascript:tree.expandAll()">Expand all</a>
        <a href="javascript:tree.collapseAll()">Collapse all</a>
      </div>
      <div id="treeDiv1"></div>

    </div>
    </form>
  </div>
    
      <div id="footerContainer">
        <div id="footer">
          <p>&nbsp;</p>
        </div>
      </div>
    </div>
  </div>
</div>



<script type="text/javascript">
//<![CDATA[

var tree;
(function() {

    function treeInit() {
        buildRandomTextNodeTree();
    }
    
    function buildRandomTextNodeTree() {
        tree = new YAHOO.widget.TreeView("treeDiv1");

        for (var i = 0; i < Math.floor((Math.random()*4) + 3); i++) {
            var tmpNode = new YAHOO.widget.TextNode("label-" + i, tree.getRoot(), true);
            // tmpNode.collapse();
            // tmpNode.expand();
            // buildRandomTextBranch(tmpNode);
            buildLargeBranch(tmpNode);
        }

       // Expand and collapse happen prior to the actual expand/collapse,
       // and can be used to cancel the operation
       tree.subscribe("expand", function(node) {
              //alert(node.index + " was expanded");
              // return false; // return false to cancel the expand
           });

       tree.subscribe("collapse", function(node) {
              //alert(node.index + " was collapsed");
           });

       // Trees with TextNodes will fire an event for when the label is clicked:
       tree.subscribe("labelClick", function(node) {
              //alert(node.index + " label was clicked");
           });


        tree.draw();
    }

    function buildLargeBranch(node) {
        if (node.depth < 10) {
            YAHOO.log("buildRandomTextBranch: " + node.index);
            for ( var i = 0; i < 10; i++ ) {
                new YAHOO.widget.TextNode(node.label + "-" + i, node, true);
            }
        }
    }

    function buildRandomTextBranch(node) {
        if (node.depth < 10) {
            YAHOO.log("buildRandomTextBranch: " + node.index);
            for ( var i = 0; i < Math.floor(Math.random() * 4) ; i++ ) {
                var tmpNode = new YAHOO.widget.TextNode(node.label + "-" + i, node, true);
                buildRandomTextBranch(tmpNode);
            }
        }
    }

    YAHOO.util.Event.addListener(window, "load", treeInit);

})();

//]]>
</script>

  </body>
</html>
 

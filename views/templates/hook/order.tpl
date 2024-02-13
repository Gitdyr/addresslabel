{*
* NOTICE OF LICENSE
* $Date: 2023/02/17 03:54:14 $
* Written by Kjeld Borch Egevang
* E-mail: kjeld@mail4us.dk
*}

<script type="text/javascript">
    $(function() {
{if $smarty.const._PS_VERSION_ >= "1.7.7"}
        let a = '<a class="btn btn-default">';
        a += '<i class="material-icons">print</i>';
        a += ' {$print}';
        a += '</a>';
        a = $(a);
        a.css('float', 'right');
        $('#addressShipping').append(a);
{else}
        let a = '<a class="btn btn-default pull-right">';
        a += '<i class="icon-print"></i>';
        a += '{$print}';
        a += '</a>';
        a = $(a);
        $('#addressShipping div.well div.row div:first').append(a);
{/if}
        $(a).click(function() {
            window.open('{$url}' + '&token={$token}');
        });
    });
</script>

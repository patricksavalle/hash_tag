# Hashtagging expressionengine plugin / addon

    A
        apples        
        avocado
        
    B
        beer
        brownies
        butter
                
    C
        ...


Hashtags in your content are automatically harvested from the first 10 data_fields + title of an entry and creates an user-presentable index.
A hashtag can contain alphanumericals and '-' and '_', and must be preceded by a space, start-of-line or &gt; like this:

> Lorem #ipsum dolor sit amet, #consectetur-adipiscing elit, sed do #Eiusmod_tempor incididunt ut labore et dolore magna aliqua. 

Or this:

> Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod_tempor incididunt ut labore et dolore magna aliqua. 
> 
> keywords: #ipsum #consectetur-adipiscing #Eiusmod_tempor

Harvesting is triggered when an entry is created or updated, on 'after_channel_entry_update'.

To iterate over entries with a specified hashtag, use

    {exp:hash_tag hash_tag="this-tag" channel="1" field_id="3"}
        {if noresults}
        {/if}
        {hash_tag}
        {entry_id}
        {url_title}
        {title}
        {field_id_3}
        ...
    {/exp:hash_tag}

The channel-argument is optional, without it all channels are included. The field_id-argument is the id of the field that should be included in the result.

To iterate over related entries uses

    {exp:hash_tag entry_id="1023" field_id="2"}
        {if noresults}
        {/if}
        {hash_tag}
        {entry_id}
        {url_title}
        {title}
        {field_id_2}
        ...
    {/exp:hash_tag}

Or

    {exp:hash_tag url_title="..." field_id="8"}
        {if noresults}
        {/if}
        {hash_tag}
        {entry_id}
        {url_title}
        {title}
        {field_id_8}
        ...
    {/exp:hash_tag}

Related entry queries are cached for 10m.

To generate a user-presentable index use (beware this can be a very large structure, normally an <UL>). 
This index is cached for 1 hour.


    {exp:hash_tag:index item_format="..." separator_format="..." channel="..."}
   
The channel-argument is optional. The format-string are sprintf-formats. Example:

      <ul>
    	  {exp:hash_tag:index 
    	    item_format="<li><a href='/index/%1$s'>%1$s (%2$s)</a></li>" 
    	    separator_format="</ul><h3>%s</h3><ul>"}
      </ul>
  

To linkfy a piece of text (convert hashtags into hrefs) use:

    {exp:hash_tag:linkify segment_1="..." class="..."}
    ...
    {/exp:hash_tag:linkify}
    
This will create links in this format around the hashtags:
  
    <a class="..." href="/{segment_1}/{hashtag}">{hashtag}</a>  
    
To remove the hash-sign from a piece of content (often neccessary for sharing on social media) use:

    {exp_hash_tag:dehash}...{/exp_hash_tag:dehash}
    
To extract hashtags as meta-tag-keyword format (comma-separated):

    {exp_hash_tag:keyword}...{/exp_hash_tag:keyword}


## Change Log

0.1 bare minimum to start experimenting

0.9 fully functional, still fine tuning on a live site

1.0 Production ready
    
## License

I don't care, mention me ;)

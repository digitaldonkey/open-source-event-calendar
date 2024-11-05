# OSEC Developer Readme

Open Source Event calendar is based on All-in-One Event Calendar 2.3.4. 

Replace Classnames With Regex 
https://regex101.com/r/COkvCD/3

SEARCH 
/<\?php\n(?<header>\s*)(?<comment>\/\*[\s\S]+?(?=\*\/)\*\/\s)(?<className>[\s\S]+?(?=extends))extends\sAi1ec_Base/gms

REPLACE
<\?php\n ${header}use Osec\\Bootstrap\\RegistryBase;\n\n${comment}${className}extends RegistryBase



downloadable font: Glyph bbox was incorrect (glyph ids 47 69 76 77 95 96 97 98 101 102 103 104 126 133 134 137 153 173 176 178 180 192 198 199 231 232 286 287 288 289 293 295 298 304 305 306 323 324 333 335 337 340 343 344 345 346 347 348 353 361 365 366 367 368 371 372 380 381 384 385) (font-family: "Timely_FontAwesome" style:normal weight:400 stretch:100 src index:1) source: data:application/x-font-woff;charset=utf-8;base64,d09GRgABAAAAAK2QAA4AAAABOwwAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAABGRlRNAAABRAAAABwAAA … QXiW5H8cQv7lAbOTI/rsxvDwyrZph9ylYc3185qjPs2MODJd80HZ5jrz/BTnV6n2iS1zE928smmDIZVpUjVTqlF3NTetmhnVzKpmTjWb/wsmC9pGAAAAAAFSd7nXAAA=
https://github.com/FortAwesome/Font-Awesome/issues/19925

Global Search for 
@instantiator
Will tell you how to instanciate the class. 


## Helper to debug EventInstances
I'm using a view 

```sql
CREATE VIEW wp_osec_event_instances_readable_date AS
SELECT id, post_id, `start`, DATE_FORMAT(FROM_UNIXTIME(`start`), '%Y-%m-%d %H:%i') AS 'start_formatted',
       `end`, DATE_FORMAT(FROM_UNIXTIME(`end`), '%Y-%m-%d %H:%i') AS 'end_formatted' FROM wp_osec_event_instances;
```



# Melcloud

plugin permettant de gèrer une climatisation Mitsubishi via Melcloud

## Configuration

2 paramètres sont obligatoires

1. le couple email:motDePasse - Exemple : *test@test.com:MonMotDePasse*
2. 2 paramètres Melcloud qui sont l'id du batiment et l'id de la climatisation - Exemple : *12345:67890*

Pour récupérer les deux paramètres, connectez vous avec Chrome à l'application [Melcloud](https://www.melcloud.com/). 
Rendez vous sur la page de gestion des climatisation puis appuyez sur la touche F12 pour ouvrir le debugger. 
Cliquez sur la climatisation et récupérez les deux identifiants

[capture]: img/getDeviceidAndBuildingId.png "Capture illustrative"


## Versions

v1.00 : basic functions
V1.01 : add temperature actioneer
v1.02 : add fan speed and mode

## Remerciements
http://mgeek.fr/blog/un-peu-de-reverse-engineering-sur-melcloud
Merci à    et la mouche pour leurs inspirations
https://forum.eedomus.com/viewtopic.php?f=15&t=5353&hilit=melcloud&start=20

## à venir
Configuration à partir du nom de la clim pour éviter la manipulation fastidieuse pour aller chercher buildingId et deviceId
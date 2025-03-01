{ lib
, pkgs
, php
, configFile ? null
, smartyConfigFileDE ? null
, smartyConfigFileEN ? null
}:

php.buildComposerProject (finalAttrs: {
  pname = "league";
  version = "1.0.0";

  src = ./.;

  postPatch = (lib.optionalString (configFile != null) ''
    cp ${configFile} config.php
    rm *.default.php
  '') + (lib.optionalString (smartyConfigFileDE != null) ''
    cp ${smartyConfigFileDE} configs/main-de.conf
  '') + (lib.optionalString (smartyConfigFileEN != null) ''
    cp ${smartyConfigFileEN} configs/main-en.conf
  '');

  vendorHash = "sha256-KuL2vLmcwiJxhP3Gkk594zsGRrN442UsUQcRtQxKKBo=";
})

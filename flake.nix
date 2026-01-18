{
  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
  inputs.flake-utils.url = "github:numtide/flake-utils";

  inputs.league-game-events.url = "github:clonkspot/league-game-events";
  inputs.league-game-events.inputs.nixpkgs.follows = "nixpkgs";

  outputs = { self, nixpkgs, flake-utils, league-game-events }:
    (flake-utils.lib.eachDefaultSystem
      (system:
        let
          pkgs = nixpkgs.legacyPackages.${system};
        in
        {
          packages.default = pkgs.callPackage ./. { };
          devShells.default = pkgs.mkShell {
            inputsFrom = [
              self.packages.${system}.default
            ];
          };
        })
    ) // {
      overlays.default = final: prev: {
        league = self.packages.${prev.system}.default;
        league-game-events = league-game-events.packages.${prev.system}.default;
      };
      nixosModules.default = import ./module.nix;
      # Container for testing
      nixosConfigurations.container = nixpkgs.lib.nixosSystem {
        system = "x86_64-linux";
        modules = [
          self.nixosModules.default
          ({ pkgs, ... }: {
            nixpkgs.overlays = [ self.overlays.default ];
            boot.isContainer = true;
            networking.firewall.allowedTCPPorts = [ 80 ];

            services.league = {
              enable = true;
              hostname = ":80";
              enableMysql = true;
              headerFileDE = pkgs.writeText "header.html" "";
              footerFileDE = pkgs.writeText "footer.html" "";
              headerFileEN = pkgs.writeText "header.html" "";
              footerFileEN = pkgs.writeText "footer.html" "";
              extraConf = ''
                require_once('lib/dummy_auth.class.php');
              '';
            };

          })
        ];
      };
    };
}

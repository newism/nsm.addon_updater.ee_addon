#!/bin/bash
path=releases/${2}
third_party_path=${path}/system/expressionengine/third_party/${1}
mkdir -p ${third_party_path}
cp config.php ${third_party_path}
cp acc.* ${third_party_path}
cp ext.* ${third_party_path}
cp ft.* ${third_party_path}
cp mcp.* ${third_party_path}
cp mod.* ${third_party_path}
cp tab.* ${third_party_path}
cp upd.* ${third_party_path}
cp -R language ${third_party_path}
cp -R libraries ${third_party_path}
cp -R models ${third_party_path}
cp -R views ${third_party_path}
cp LICENSE.md ${path}
cp README.md ${path}
cp -R themes ${path}
cp -R templates ${path}/system/expressionengine/
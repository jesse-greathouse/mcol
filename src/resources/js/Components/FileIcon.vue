<template>
    <span>
        <component v-bind:is="icon"></component>
    </span>
</template>

<script>
import { has } from '@/funcs'
import DirectoryIcon from '@/Components/FileIcons/Directory.vue'
import DiscImageIcon from '@/Components/FileIcons/DiscImage.vue'
import EbookIcon from '@/Components/FileIcons/Ebook.vue'
import ExeIcon from '@/Components/FileIcons/Exe.vue'
import FlacIcon from '@/Components/FileIcons/Flac.vue'
import GenericIcon from '@/Components/FileIcons/Generic.vue'
import LinkedLibraryIcon from '@/Components/FileIcons/LinkedLibrary.vue'
import MkvIcon from '@/Components/FileIcons/Mkv.vue'
import MusicIcon from '@/Components/FileIcons/Music.vue'
import MobileIcon from '@/Components/FileIcons/Mobile.vue'
import Mp4Icon from '@/Components/FileIcons/Mp4.vue'
import NspIcon from '@/Components/FileIcons/Nsp.vue'
import PackageIcon from '@/Components/FileIcons/Package.vue'
import PdfIcon from '@/Components/FileIcons/Pdf.vue'
import RarIcon from '@/Components/FileIcons/Rar.vue'
import SrtIcon from '@/Components/FileIcons/Srt.vue'
import TarIcon from '@/Components/FileIcons/Tar.vue'
import TxtIcon from '@/Components/FileIcons/Txt.vue'
import VideoIcon from '@/Components/FileIcons/Video.vue'
import ZipIcon from '@/Components/FileIcons/Zip.vue'

const extensionToComponentMap = {
    avi: 'VideoIcon',
    dll: 'LinkedLibraryIcon',
    dmg: 'DiscImageIcon',
    epub: 'EbookIcon',
    exe: 'ExeIcon',
    flac: 'FlacIcon',
    iso: 'DiscImageIcon',
    m4a: 'MusicIcon',
    m4b: 'EbookIcon',
    m4v: 'VideoIcon',
    mkv: 'MkvIcon',
    mobi: 'MobileIcon',
    mp3: 'MusicIcon',
    mp4: 'Mp4Icon',
    nsp: 'NspIcon',
    pdf: 'PdfIcon',
    pkg: 'PackageIcon',
    rar: 'RarIcon',
    srt: 'SrtIcon',
    tar: 'TarIcon',
    txt: 'TxtIcon',
    wav: 'MusicIcon',
    zip: 'ZipIcon',
}

export default {
    components: {
        DirectoryIcon, DiscImageIcon, EbookIcon, ExeIcon,
        FlacIcon, GenericIcon, LinkedLibraryIcon, MkvIcon, MusicIcon,
        MobileIcon, Mp4Icon, NspIcon, PackageIcon, PdfIcon,
        RarIcon, SrtIcon, TarIcon, TxtIcon, VideoIcon, ZipIcon
    },
    props: {
        extension: String,
        isDir: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        let component = 'GenericIcon'
        if (this.isDir) {
            component = 'DirectoryIcon'
        }

        if (has(extensionToComponentMap, this.extension)) {
            component = extensionToComponentMap[this.extension]
        }

        return {
            icon: component
        }
    }
}
</script>

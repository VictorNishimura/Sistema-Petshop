const botaoAbrirCamera = document.getElementById('abrir_camera');
const botaoCapturarFoto = document.getElementById('capturar_foto');
const botaoFecharCamera = document.getElementById('fechar_camera');
const videoCamera = document.getElementById('camera_video');
const canvasCamera = document.getElementById('camera_canvas');
const previewCamera = document.getElementById('camera_preview');
const inputFotoCamera = document.getElementById('foto_camera');
const inputFotoPerfil = document.getElementById('foto_perfil');
const avisoCamera = document.getElementById('camera_aviso');
let streamCamera = null;

function mostrarAvisoCamera(mensagem) {
    avisoCamera.textContent = mensagem;
    avisoCamera.classList.toggle('d-none', mensagem === '');
}

function pararCamera() {
    if (streamCamera) {
        streamCamera.getTracks().forEach((track) => track.stop());
        streamCamera = null;
    }

    videoCamera.classList.add('d-none');
    botaoCapturarFoto.classList.add('d-none');
    botaoFecharCamera.classList.add('d-none');
}

botaoAbrirCamera.addEventListener('click', async () => {
    mostrarAvisoCamera('');

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        mostrarAvisoCamera('Este navegador nao permite abrir a camera.');
        return;
    }

    try {
        streamCamera = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        videoCamera.srcObject = streamCamera;
        videoCamera.classList.remove('d-none');
        botaoCapturarFoto.classList.remove('d-none');
        botaoFecharCamera.classList.remove('d-none');
    } catch (error) {
        mostrarAvisoCamera('Nao foi possivel acessar a camera. Verifique a permissao do navegador.');
    }
});

botaoCapturarFoto.addEventListener('click', () => {
    canvasCamera.width = videoCamera.videoWidth;
    canvasCamera.height = videoCamera.videoHeight;
    canvasCamera.getContext('2d').drawImage(videoCamera, 0, 0);

    const foto = canvasCamera.toDataURL('image/jpeg', 0.9);
    inputFotoCamera.value = foto;
    inputFotoPerfil.value = '';
    previewCamera.src = foto;
    previewCamera.classList.remove('d-none');
    pararCamera();
});

botaoFecharCamera.addEventListener('click', pararCamera);

inputFotoPerfil.addEventListener('change', () => {
    if (inputFotoPerfil.files.length > 0) {
        inputFotoCamera.value = '';
        previewCamera.classList.add('d-none');
    }
});

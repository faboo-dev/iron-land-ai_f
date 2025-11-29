jQuery(document).ready(function ($) {
    $('#ai-search-btn').on('click', function () {
        var query = $('#ai-query').val();
        if (!query) {
            alert('질문을 입력해주세요!');
            return;
        }

        // 로딩 표시
        $('#ai-results').html('<div class="loading-spinner">AI가 여행 기록을 찾아보고 있어요... ✈️</div>');
        $('#ai-search-btn').prop('disabled', true);

        $.ajax({
            url: '/wp-json/travel/v1/smart-search', // 워드프레스 API 엔드포인트
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                query: query
            }),
            success: function (response) {
                $('#ai-search-btn').prop('disabled', false);

                if (response.success) {
                    var content = response.main_content.content;
                    // 줄바꿈 처리
                    content = content.replace(/\n/g, '<br>');

                    var html = '<div class="ai-answer-box">';
                    html += '<div class="ai-header">🤖 철산랜드 AI 답변</div>';
                    html += '<div class="ai-body">' + content + '</div>';
                    html += '</div>';

                    $('#ai-results').html(html);
                } else {
                    $('#ai-results').html('<div class="error-msg">죄송합니다. 오류가 발생했습니다: ' + response.error + '</div>');
                }
            },
            error: function (xhr, status, error) {
                $('#ai-search-btn').prop('disabled', false);
                console.error('AI Search Error:', error);
                $('#ai-results').html('<div class="error-msg">서버 통신 중 오류가 발생했습니다.</div>');
            }
        });
    });
});

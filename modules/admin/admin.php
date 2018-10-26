<?php

class internalmodule_Reconfigure extends uBasicModule implements iAdminModule
{
  // title: the title of this page, to appear in header box and navigation
  public function GetTitle() { return 'Configuration'; }

  public function GetOptions() { return ALWAYS_ACTIVE; }

  public function SetupParents() { }

  public static function Initialise()
  {
    self::AddParent('/');
  }

  public function GetSortOrder() { return 10000 - 1; }

  public function RunModule()
  {
    echo '<h1>' . $this->GetTitle() . '</h1>';
    echo '<div class="layoutListSection module-content">';
    uConfig::ShowConfig();
    echo '</div>';
  }
}

//uEvents::AddCallback('AfterRunModule','uDashboard::DrawDashboard','uDashboard');
class uDashboard extends uBasicModule implements iAdminModule
{
  // title: the title of this page, to appear in header box and navigation
  public function GetTitle() { return 'Dashboard'; }

  public function GetOptions() { return ALWAYS_ACTIVE; }

  public function GetSortOrder() { return -10000; }

  public function GetURL($filters = null)
  {
    $qs = $filters ? '?' . http_build_query($filters) : '';
    return PATH_REL_CORE . 'index.php' . $qs;
  }

  public static function Initialise()
  {
    utopia::RegisterAjax('toggle_debug', 'uDashboard::toggleDebug');
    uEvents::AddCallback('AfterRunModule', 'uDashboard::SetupMenu', utopia::GetCurrentModule());
    self::AddParent('/');
  }

  public function SetupParents() { }

  public static function SetupMenu()
  {
    if(uEvents::TriggerEvent('CanAccessModule', __CLASS__) !== false)
    {
      uAdminBar::AddItem(
        '<a class="btn dashboard-link" href="' . PATH_REL_CORE . '"><span/>Dashboard</a>',
        false,
        -100
      );
    }
  }

  public static function toggleDebug()
  {
    utopia::DebugMode(!utopia::DebugMode());
    die('window.location.reload();');
  }

  public static function DrawDashboard()
  {
    // get large widget area
    ob_start();
    uEvents::TriggerEvent('ShowDashboard');
    $largeContent = ob_get_clean();
    if($largeContent)
    {
      echo '<div class="dash-large">' . $largeContent . '</div>';
    }

    // get small widget area
    $smallContent = '';
    $w = utopia::GetModulesOf('uDashboardWidget');
    foreach($w as $wid)
    {
      $wid = $wid['module_name'];
      $ref = new ReflectionClass($wid);
      ob_start();
      if($ref->hasMethod('Draw100'))
      {
        $wid::Draw100();
      }
      else if($ref->hasMethod('Draw50'))
      {
        $wid::Draw50();
      }
      else if($ref->hasMethod('Draw25'))
      {
        $wid::Draw25();
      }
      $content = ob_get_clean();
      if(!$content)
      {
        continue;
      }
      $smallContent .= '<div class="widget-container ' . $wid . '"><h1>' . $wid::GetTitle(
        ) . '</h1><div class="module-content">' . $content . '</div></div>';
    }
    if($smallContent)
    {
      echo '<div class="dash-small">' . $smallContent . '</div>';
    }
  }

  public function RunModule()
  {
    self::DrawDashboard();
  }
}
